CREATE TABLE IF NOT EXISTS accounts (
  id VARCHAR(64) PRIMARY KEY,
  balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  metadata JSON NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

SET @exists := (SELECT COUNT(1)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'accounts'
                AND INDEX_NAME = 'idx_accounts_updated_at');

SET @sql := IF(@exists = 0,
    'CREATE INDEX idx_accounts_updated_at ON accounts(updated_at)',
    'SELECT "index exists"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- users table for the PHP API (canonical schema)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nazwa_uzytkownika VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  haslo VARCHAR(255) NOT NULL,
  stan_konta DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  nr_konta VARCHAR(16) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- event_queue table for DB-backed retry queue
CREATE TABLE IF NOT EXISTS event_queue (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  attempts INT NOT NULL DEFAULT 0,
  payload JSON NOT NULL,
  processed TINYINT(1) NOT NULL DEFAULT 0,
  last_error TEXT DEFAULT NULL,
  processed_at TIMESTAMP NULL DEFAULT NULL,
  locked_by VARCHAR(128) DEFAULT NULL,
  locked_at TIMESTAMP NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS idx_event_queue_processed ON event_queue(processed, ts);

-- dead_letter table records permanently failed events for inspection
CREATE TABLE IF NOT EXISTS dead_letter (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT NULL,
  ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  attempts INT NOT NULL DEFAULT 0,
  payload JSON NOT NULL,
  last_error TEXT DEFAULT NULL,
  failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_dead_letter_failed_at ON dead_letter(failed_at);
