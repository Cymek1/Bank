/**
 * Integration tests (simple):
 * - Publikacja przez Producer (HTTP) -> RabbitMQ -> Consumer zapisuje do DB
 * - Fallback HTTP: jeśli RabbitMQ down, Producer używa HTTP fallback -> Consumer zapisuje do DB
 * - Konflikt: wysyłamy starszy i potem nowszy update i sprawdzamy, że DB ma nowszy
 */

const axios = require('axios');
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

const PRODUCER_URL = process.env.PRODUCER_URL || 'http://localhost:4000';
const CONSUMER_URL = process.env.CONSUMER_URL || 'http://localhost:4001';
const MYSQL_CONFIG = {
  host: process.env.MYSQL_HOST || '127.0.0.1',
  port: process.env.MYSQL_PORT || 3306,
  user: process.env.MYSQL_USER || 'bank',
  password: process.env.MYSQL_PASSWORD || 'bankpass',
  database: process.env.MYSQL_DB || 'bank_db'
};

let db;

beforeAll(async () => {
  db = await mysql.createPool(MYSQL_CONFIG);
});

afterAll(async () => {
  await db.end();
});

test('full flow: publish -> consume -> db insert', async () => {
  const id = 'testacc-' + Date.now();
  const payload = { id, balance: 123.45, metadata: { source: 'test' }, updated_at: new Date().toISOString() };
  // send to producer
  const p = await axios.post(PRODUCER_URL + '/api/publish', payload);
  expect([200,201,202].includes(p.status)).toBeTruthy();

  // wait a bit for consumer to process
  await new Promise(r => setTimeout(r, 1000));

  const [rows] = await db.query('SELECT * FROM accounts WHERE id = ?', [id]);
  expect(rows.length).toBe(1);
  expect(Number(rows[0].balance)).toBeCloseTo(123.45, 2);
});

test('conflict resolution: older -> newer', async () => {
  const id = 'conflict-' + Date.now();
  const older = { id, balance: 50.00, metadata: { v: 'old' }, updated_at: new Date(Date.now() - 10000).toISOString() };
  const newer = { id, balance: 77.77, metadata: { v: 'new' }, updated_at: new Date().toISOString() };

  // send older
  await axios.post(PRODUCER_URL + '/api/publish', older);
  await new Promise(r => setTimeout(r, 500));
  // send newer
  await axios.post(PRODUCER_URL + '/api/publish', newer);
  await new Promise(r => setTimeout(r, 1000));

  const [rows] = await db.query('SELECT * FROM accounts WHERE id = ?', [id]);
  expect(rows.length).toBe(1);
  expect(Number(rows[0].balance)).toBeCloseTo(77.77, 2);
});

test('fallback HTTP: simulate rabbit down by posting directly to consumer', async () => {
  const id = 'fallback-' + Date.now();
  const payload = { id, balance: 10.00, metadata: { fallback: true }, updated_at: new Date().toISOString() };
  const res = await axios.post(CONSUMER_URL + '/api/receive', payload);
  expect(res.status).toBe(200);
  await new Promise(r => setTimeout(r, 500));
  const [rows] = await db.query('SELECT * FROM accounts WHERE id = ?', [id]);
  expect(rows.length).toBe(1);
  expect(Number(rows[0].balance)).toBeCloseTo(10.00, 2);
});
