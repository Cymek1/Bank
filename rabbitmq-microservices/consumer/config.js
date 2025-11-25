module.exports = {
  RABBIT_URL: process.env.RABBIT_URL || 'amqp://guest:guest@localhost:5672',
  QUEUE_NAME: process.env.QUEUE_NAME || 'bank_events',
  PORT: process.env.PORT || 4001,
  MYSQL: {
    host: process.env.MYSQL_HOST || '127.0.0.1',
    port: process.env.MYSQL_PORT || 3306,
    user: process.env.MYSQL_USER || 'bank',
    password: process.env.MYSQL_PASSWORD || 'bankpass',
    database: process.env.MYSQL_DB || 'bank_db'
  }
  ,
  // Consumer DB retry settings
  DB_RETRY_MAX: parseInt(process.env.CONSUMER_DB_RETRY_MAX || '3', 10),
  DB_RETRY_BASE_MS: parseInt(process.env.CONSUMER_DB_RETRY_BASE_MS || '200', 10)
};
