-- ─── Migration: Add category column to jokes table ───────────────────
-- Run this once in phpMyAdmin on your live database.
-- Safe to run on an existing table — it only adds the new column.

ALTER TABLE jokes
  ADD COLUMN category VARCHAR(80) NULL DEFAULT NULL AFTER submitted_by;

-- Optional: add an index to speed up category filtering
CREATE INDEX idx_jokes_category ON jokes (category);

-- ─── Admin accounts table ─────────────────────────────────────────────
-- Stores hashed admin credentials. Passwords are NEVER stored in plaintext.
-- After running this migration, visit setup_admin.php ONCE to create your
-- admin account, then delete setup_admin.php from the server immediately.

CREATE TABLE IF NOT EXISTS admins (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(80)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;