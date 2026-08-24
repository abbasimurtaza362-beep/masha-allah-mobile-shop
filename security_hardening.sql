USE masha_allah_shop;

-- Run once for an existing database before deploying the hardened PHP files.
ALTER TABLE users
  ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER last_otp_sent_at;

CREATE TABLE security_rate_limits (
  bucket_hash CHAR(64) PRIMARY KEY,
  attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  window_started_at DATETIME NOT NULL,
  blocked_until DATETIME NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_rate_cleanup (window_started_at, blocked_until)
) ENGINE=InnoDB;
