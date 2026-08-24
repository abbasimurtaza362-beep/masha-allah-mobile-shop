USE masha_allah_shop;
ALTER TABLE users
  ADD COLUMN status ENUM('active','blocked') NOT NULL DEFAULT 'active' AFTER role,
  ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN otp_hash VARCHAR(255) NULL AFTER email_verified,
  ADD COLUMN otp_expires_at DATETIME NULL AFTER otp_hash,
  ADD COLUMN otp_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER otp_expires_at,
  ADD COLUMN last_otp_sent_at DATETIME NULL AFTER otp_attempts,
  ADD INDEX idx_users_role (role),
  ADD INDEX idx_users_status (status),
  ADD INDEX idx_users_verified (email_verified);
