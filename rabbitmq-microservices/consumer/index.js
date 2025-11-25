const amqplib = require('amqplib');
const express = require('express');
const bodyParser = require('body-parser');
const mysql = require('mysql2/promise');
const { backoff } = require('./utils/backoff');
const logger = require('./utils/logger');
const config = require('./config');
const Ajv = require('ajv');
const addFormats = require('ajv-formats');
const eventSchema = require('./schemas/event.json');
const client = require('prom-client');

const ajv = new Ajv({ allErrors: true, strict: false });
addFormats(ajv);
const validateEvent = ajv.compile(eventSchema);

let channel = null;
let conn = null;
let db = null;
let rabbitConnected = false;

// Simple in-memory circuit breaker for DB writes
const circuit = {
  failureCount: 0,
  successCount: 0,
  state: 'CLOSED', // CLOSED, OPEN
  openedAt: null,
  FAILURE_THRESHOLD: 5,
  OPEN_TIMEOUT_MS: 30000
};

// Prometheus metrics
const collectDefaultMetrics = client.collectDefaultMetrics;
collectDefaultMetrics({ prefix: 'consumer_' });
const gaugeEventQueue = new client.Gauge({ name: 'consumer_event_queue_count', help: 'Event queue rows' });
const gaugeDeadLetter = new client.Gauge({ name: 'consumer_dead_letter_count', help: 'Dead letter rows' });

async function updateMetrics() {
  try {
    if (!db) return;
    const [q1] = await db.query('SELECT COUNT(*) as c FROM event_queue');
    const [q2] = await db.query('SELECT COUNT(*) as c FROM dead_letter');
    gaugeEventQueue.set(q1[0].c || 0);
    gaugeDeadLetter.set(q2[0].c || 0);
  } catch (e) {
    // ignore
  }
}
setInterval(updateMetrics, 5000);

// DB connect
async function connectDB() {
  db = await mysql.createPool({
    host: config.MYSQL.host,
    port: config.MYSQL.port,
    user: config.MYSQL.user,
    password: config.MYSQL.password,
    database: config.MYSQL.database,
    waitForConnections: true,
    connectionLimit: 10
  });

  logger.info('Connected to MySQL');
}

// RABBITMQ connect
async function connectRabbit(attempt = 0) {
  try {
    conn = await amqplib.connect(config.RABBIT_URL);

    conn.on('error', err => {
      logger.error('RabbitMQ connection error', { err: err.message });
      rabbitConnected = false;
    });

    conn.on('close', () => {
      logger.warn('RabbitMQ connection closed (consumer)');
      rabbitConnected = false;
      setTimeout(() => connectRabbit(attempt + 1), backoff(attempt));
    });

    channel = await conn.createChannel();
    await channel.assertQueue(config.QUEUE_NAME, { durable: true });

    rabbitConnected = true;
    logger.info('Connected to RabbitMQ (consumer)');

    // message consume
    channel.consume(config.QUEUE_NAME, async msg => {
      if (msg !== null) {
          try {
            const content = JSON.parse(msg.content.toString());
            await processMessage(content);
            channel.ack(msg);
          } catch (err) {
            logger.error('Failed to process message', { err: err.message });
            // Requeue the message for redelivery. In production consider DLX/X-death policies
            // so messages are moved to a dead-letter queue after repeated redeliveries.
            try {
              channel.nack(msg, false, true);
            } catch (e) {
              logger.error('Failed to nack message', { err: e.message });
            }
          }
        }
    });

  } catch (err) {
    logger.error('Consumer RabbitMQ connect failed', { err: err.message });
    rabbitConnected = false;
    setTimeout(() => connectRabbit(attempt + 1), backoff(attempt));
  }
}


// message logic
async function processMessage(message) {
  // circuit-breaker check
  if (circuit.state === 'OPEN') {
    const now = Date.now();
    if (now - circuit.openedAt > circuit.OPEN_TIMEOUT_MS) {
      // half-open: allow attempts
      circuit.state = 'CLOSED';
      circuit.failureCount = 0;
    } else {
      throw new Error('CircuitOpen');
    }
  }
  if (!message || !message.id) {
    logger.warn('Invalid message, skipping', { message });
    return;
  }

  // basic validation
  if (message.balance != null && typeof message.balance !== 'number' && isNaN(Number(message.balance))) {
    logger.warn('Invalid balance type, skipping', { id: message.id, balance: message.balance });
    return;
  }

  const incomingUpdatedAt = message.updated_at ? new Date(message.updated_at) : new Date();
  const maxRetries = config.DB_RETRY_MAX || 3;
  const baseMs = config.DB_RETRY_BASE_MS || 200;

  let attempt = 0;
  let lastErr = null;
  const connection = await db.getConnection();

  while (attempt < maxRetries) {
    attempt++;
    try {
      await connection.beginTransaction();

      const [rows] = await connection.query('SELECT updated_at FROM accounts WHERE id = ?', [message.id]);

      if (rows.length === 0) {
        await connection.query(
          'INSERT INTO accounts (id, balance, metadata, updated_at) VALUES (?, ?, ?, ?)',
          [message.id, message.balance || 0.0, JSON.stringify(message.metadata || {}), incomingUpdatedAt]
        );
        logger.info('Inserted new account', { id: message.id, attempt });
      } else {
        const dbUpdatedAt = new Date(rows[0].updated_at);

        if (incomingUpdatedAt > dbUpdatedAt) {
          await connection.query(
            'UPDATE accounts SET balance = ?, metadata = ?, updated_at = ? WHERE id = ?',
            [message.balance || 0.0, JSON.stringify(message.metadata || {}), incomingUpdatedAt, message.id]
          );
          logger.info('Updated account (incoming newer)', { id: message.id, attempt });
        } else {
          logger.info('Skipped update (db newer)', { id: message.id });
        }
      }

      await connection.commit();
      lastErr = null;
      // success -> bump circuit success counter
      circuit.successCount = (circuit.successCount || 0) + 1;
      circuit.failureCount = 0;
      break; // success
    } catch (err) {
      await connection.rollback();
      lastErr = err;
      logger.error('DB error during processMessage', { err: err.message, attempt });
      // simple backoff before retrying
      const waitMs = baseMs * Math.pow(2, attempt - 1);
      await new Promise(r => setTimeout(r, waitMs));
      // try to continue loop and retry
    }
  }

  try {
    connection.release();
  } catch (e) {
    logger.warn('Failed to release connection', { err: e.message });
  }

  if (lastErr) {
    // failure -> bump circuit failure counter and maybe open
    circuit.failureCount = (circuit.failureCount || 0) + 1;
    circuit.successCount = 0;
    if (circuit.failureCount >= circuit.FAILURE_THRESHOLD) {
      circuit.state = 'OPEN';
      circuit.openedAt = Date.now();
      logger.error('Circuit opened due to repeated DB failures');
    }
    // bubble up so the caller (message consumer) can decide to requeue
    throw lastErr;
  }
}


// Start
(async () => {
  await connectDB();
  await connectRabbit();

  const app = express();
  app.use(bodyParser.json());

  // expose metrics for Prometheus
  app.get('/metrics', async (req, res) => {
    try {
      res.set('Content-Type', client.register.contentType);
      res.end(await client.register.metrics());
    } catch (e) {
      res.status(500).end(e.message);
    }
  });

  app.post('/api/receive', async (req, res) => {
    if (!req.body || !req.body.id) {
      return res.status(400).json({ error: 'Payload must contain id' });
    }

    if (!validateEvent(req.body)) {
      logger.warn('Validation failed for incoming HTTP message', { errors: validateEvent.errors });
      return res.status(400).json({ error: 'validation_failed', details: validateEvent.errors });
    }

    try {
      await processMessage(req.body);
      return res.json({ status: 'ok' });
    } catch (err) {
      logger.error('HTTP fallback failed', { err: err.message });
      return res.status(500).json({ error: 'processing_failed' });
    }
  });

  // testing helper: fetch account by id (returns null if not found)
  app.get('/api/account/:id', async (req, res) => {
    const id = req.params.id;
    try {
      const [rows] = await db.query('SELECT id, balance, metadata, updated_at FROM accounts WHERE id = ?', [id]);
      if (rows.length === 0) return res.status(404).json({ error: 'not_found' });
      // metadata stored as JSON string; mysql2 returns it as object when JSON column, but keep safe
      const row = rows[0];
      return res.json({ account: row });
    } catch (err) {
      logger.error('Failed to fetch account', { err: err.message });
      return res.status(500).json({ error: 'db_error' });
    }
  });

  app.get('/health', (req, res) => {
    res.json({ status: 'ok', rabbit: rabbitConnected });
  });

  app.listen(config.PORT, () => {
    logger.info(`Consumer running on port ${config.PORT}`);
  });
})();
