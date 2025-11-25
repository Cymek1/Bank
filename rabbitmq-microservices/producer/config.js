const path = require('path');

module.exports = {
  RABBIT_URL: process.env.RABBIT_URL || 'amqp://guest:guest@localhost:5672',
  QUEUE_NAME: process.env.QUEUE_NAME || 'bank_events',
  HTTP_FALLBACK_URL: process.env.HTTP_FALLBACK_URL || 'http://localhost:4001/api/receive', // consumer HTTP
  PORT: process.env.PORT || 4000,
  // COMM_MODE: 'rabbit' | 'http' | 'both' (try rabbit then http fallback)
  COMM_MODE: process.env.COMM_MODE || 'rabbit',
  BUFFER_FILE: process.env.BUFFER_FILE || path.join(__dirname, 'utils', 'buffer.json')
};
