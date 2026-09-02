-- 009_enhance_run_tokens_and_settings.sql

ALTER TABLE run_tokens ADD COLUMN IF NOT EXISTS start_nonce VARCHAR(64) NULL;
ALTER TABLE run_tokens ADD COLUMN IF NOT EXISTS session_hash VARCHAR(64) NULL;

CREATE TABLE IF NOT EXISTS system_settings (
    key VARCHAR(64) PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Populate initial system settings
INSERT INTO system_settings (key, value, updated_at)
VALUES 
    ('season_title', 'Season 1 // Void Genesis', CURRENT_TIMESTAMP),
    ('season_end_date', '2026-12-31', CURRENT_TIMESTAMP),
    ('maintenance_mode', '0', CURRENT_TIMESTAMP),
    ('max_score_per_sec', '280', CURRENT_TIMESTAMP),
    ('clock_drift_tol', '15', CURRENT_TIMESTAMP)
ON CONFLICT (key) DO NOTHING;
