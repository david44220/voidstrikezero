-- 004_create_matches_and_run_tokens_table.sql

CREATE TABLE IF NOT EXISTS run_tokens (
    id SERIAL PRIMARY KEY,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    user_id INTEGER NULL REFERENCES users (id) ON DELETE SET NULL,
    vehicle_class VARCHAR(50) NOT NULL,
    arena_id VARCHAR(50) NOT NULL,
    difficulty VARCHAR(20) NOT NULL,
    mode VARCHAR(20) NOT NULL DEFAULT 'quick',
    challenge_id VARCHAR(36) NULL,
    seed VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_run_tokens_hash ON run_tokens (token_hash);
CREATE INDEX IF NOT EXISTS idx_run_tokens_user ON run_tokens (user_id);

CREATE TABLE IF NOT EXISTS matches (
    id VARCHAR(36) PRIMARY KEY,
    user_id INTEGER NULL REFERENCES users (id) ON DELETE SET NULL,
    vehicle_class VARCHAR(50) NOT NULL,
    arena_id VARCHAR(50) NOT NULL,
    difficulty VARCHAR(20) NOT NULL,
    mode VARCHAR(20) NOT NULL DEFAULT 'quick',
    score INTEGER NOT NULL DEFAULT 0,
    waves_cleared INTEGER NOT NULL DEFAULT 0,
    kills INTEGER NOT NULL DEFAULT 0,
    accuracy NUMERIC(5, 2) NOT NULL DEFAULT 0.00,
    combo_max INTEGER NOT NULL DEFAULT 0,
    duration_seconds INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'completed', -- 'completed', 'flagged', 'invalidated'
    anti_cheat_flags JSONB NULL,
    run_token_hash VARCHAR(64) NULL,
    start_nonce VARCHAR(64) NULL,
    telemetry_summary JSONB NULL,
    ghost_data JSONB NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_matches_leaderboard ON matches (status, score DESC, finished_at ASC);
CREATE INDEX IF NOT EXISTS idx_matches_user_finished ON matches (user_id, finished_at DESC);
CREATE INDEX IF NOT EXISTS idx_matches_finished_at ON matches (finished_at DESC);
CREATE INDEX IF NOT EXISTS idx_matches_status ON matches (status);
CREATE INDEX IF NOT EXISTS idx_matches_mode ON matches (mode);
