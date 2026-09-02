-- 007_create_notifications_table.sql

CREATE TABLE IF NOT EXISTS notifications (
    id VARCHAR(36) PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    title_en VARCHAR(150) NOT NULL,
    title_fr VARCHAR(150) NOT NULL,
    message_en TEXT NOT NULL,
    message_fr TEXT NOT NULL,
    data JSONB NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications (user_id, is_read, created_at DESC);
