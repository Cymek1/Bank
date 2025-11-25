const amqplib = require('amqplib');
const express = require('express');
const bodyParser = require('body-parser');
const fs = require('fs/promises');
const path = require('path');
const axios = require('axios');
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
let rabbitConnected = false;

// lokalny buffer JSON dla wiadomości offline
const bufferPath = config.BUFFER_FILE;

// Prometheus metrics
const collectDefaultMetrics = client.collectDefaultMetrics;
collectDefaultMetrics({ prefix: 'producer_' });
const gaugeBuffer = new client.Gauge({ name: 'producer_buffer_size', help: 'Number of messages in local buffer' });
async function updateProducerMetrics() {
  try {
    const buf = await loadBuffer();
    gaugeBuffer.set(Array.isArray(buf) ? buf.length : 0);
  } catch (e) {
    // ignore
  }
}
setInterval(updateProducerMetrics, 5000);

async function loadBuffer() {
  try {
    const data = await fs.readFile(bufferPath, 'utf-8');
    return JSON.parse(data);
  } catch {
    return [];
  }
}

async function saveBuffer(data) {
  await fs.mkdir(path.dirname(bufferPath), { recursive: true });
  await fs.writeFile(bufferPath, JSON.stringify(data, null, 2));
}

function validateMessage(msg) {
  if (!msg || typeof msg !== 'object') return false;
  if (!msg.id) return false;
  // balance if present should be number-like
  if (msg.balance != null && typeof msg.balance !== 'number' && isNaN(Number(msg.balance))) return false;
  return true;
}

async function sendHttpWithRetry(url, payload, maxAttempts = 5) {
  let attempt = 0;
  while (attempt < maxAttempts) {
    try {
      await axios.post(url, payload, { timeout: 5000 });
      return true;
    } catch (err) {
      const ms = backoff(attempt);
      logger.warn('HTTP send failed, retrying', { attempt, err: err.message, wait_ms: ms });
      await new Promise(r => setTimeout(r, ms));
      attempt += 1;
    }
  }
  return false;
}

async function connectRabbit(attempt = 0) {
  if (config.COMM_MODE === 'http') {
    logger.info('COMM_MODE=http - skipping RabbitMQ connect (producer)');
    return;
  }
  try {
    conn = await amqplib.connect(config.RABBIT_URL);
    conn.on('error', err => {
      logger.error('RabbitMQ connection error', { err: err.message });
      rabbitConnected = false;
    });

    conn.on('close', () => {
      logger.warn('RabbitMQ connection closed (producer)');
      rabbitConnected = false;

      setTimeout(() => connectRabbit(attempt + 1), backoff(attempt));
    });

    channel = await conn.createChannel();
    await channel.assertQueue(config.QUEUE_NAME, { durable: true });

    rabbitConnected = true;
    logger.info('Connected to RabbitMQ (producer)');

    // flush buffered messages
    const buffer = await loadBuffer();
    if (buffer.length > 0) {
      logger.info(`Flushing ${buffer.length} buffered messages`);
      for (const msg of buffer) {
        channel.sendToQueue(config.QUEUE_NAME, Buffer.from(JSON.stringify(msg)), {
          persistent: true
        });
      }
      await saveBuffer([]);
    }

  } catch (err) {
    logger.error('Producer RabbitMQ connect failed', { err: err.message });
    rabbitConnected = false;
    setTimeout(() => connectRabbit(attempt + 1), backoff(attempt));
  }
}

async function publishMessage(message) {
  if (!validateMessage(message)) {
    logger.warn('Invalid message, refusing to send', { message });
    return;
  }

  // If COMM_MODE forces HTTP, send over HTTP
  if (config.COMM_MODE === 'http') {
    const ok = await sendHttpWithRetry(config.HTTP_FALLBACK_URL, message);
    if (!ok) {
      logger.error('HTTP send failed, buffering message', { id: message.id });
      const buffer = await loadBuffer();
      buffer.push(message);
      await saveBuffer(buffer);
    } else {
      logger.info('Message sent via HTTP', { id: message.id });
    }
    return;
  }

  // Try rabbit first for 'rabbit' and 'both'
  if (rabbitConnected && channel) {
    try {
      channel.sendToQueue(
        config.QUEUE_NAME,
        Buffer.from(JSON.stringify(message)),
        { persistent: true }
      );
      logger.info('Message sent (rabbit)', { id: message.id });
      return;
    } catch (err) {
      logger.warn('Rabbit send failed', { err: err.message });
    }
  }

  // If we get here, rabbit unavailable. If COMM_MODE is 'both', try HTTP fallback; otherwise buffer.
  if (config.COMM_MODE === 'both') {
    const ok = await sendHttpWithRetry(config.HTTP_FALLBACK_URL, message);
    if (ok) {
      logger.info('Message sent via HTTP fallback', { id: message.id });
      return;
    }
  }

  logger.warn('Rabbit unavailable, buffering message', { id: message.id });
  const buffer = await loadBuffer();
  buffer.push(message);
  await saveBuffer(buffer);
}


// START SERVER
(async () => {
  await connectRabbit();

  const app = express();
  app.use(bodyParser.json());

  app.post('/api/send', async (req, res) => {
    const message = {
      id: req.body.id,
      balance: req.body.balance,
      metadata: req.body.metadata || {},
      updated_at: new Date().toISOString()
    };

    if (!validateEvent(message)) {
      logger.warn('Validation failed for outgoing message', { errors: validateEvent.errors });
      return res.status(400).json({ error: 'validation_failed', details: validateEvent.errors });
    }

    await publishMessage(message);
    return res.json({ status: 'queued' });
  });

  app.get('/health', (req, res) => {
    res.json({ status: 'ok', rabbit: rabbitConnected });
  });

  app.get('/metrics', async (req, res) => {
    try {
      res.set('Content-Type', client.register.contentType);
      res.end(await client.register.metrics());
    } catch (e) {
      res.status(500).end(e.message);
    }
  });

  app.listen(config.PORT, () => {
    logger.info(`Producer running on port ${config.PORT}`);
  });
})();
