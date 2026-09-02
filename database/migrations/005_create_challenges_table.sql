-- 005_create_challenges_table.sql

CREATE TABLE IF NOT EXISTS challenges (
    id VARCHAR(36) PRIMARY KEY,
    creator_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    target_score INTEGER NOT NULL,
    vehicle_class VARCHAR(50) NOT NULL,
    arena_id VARCHAR(50) NOT NULL,
    difficulty VARCHAR(20) NOT NULL,
    expires_at TIMESTAMP NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- 'active', 'completed', 'expired'
    best_attempt_id VARCHAR(36) NULL,
    challenger_count INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_challenges_creator ON challenges (creator_id);
CREATE INDEX IF NOT EXISTS idx_challenges_status ON challenges (status);
CREATE INDEX IF NOT EXISTS idx_challenges_created ON challenges (created_at DESC);

CREATE TABLE IF NOT EXISTS challenge_attempts (
    id VARCHAR(36) PRIMARY KEY,
    challenge_id VARCHAR(36) NOT NULL REFERENCES challenges (id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    match_id VARCHAR(36) NOT NULL REFERENCES matches (id) ON DELETE CASCADE,
    score INTEGER NOT NULL,
    is_beaten BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_challenge_attempts_challenge ON challenge_attempts (challenge_id, score DESC);
CREATE INDEX IF NOT EXISTS idx_challenge_attempts_user ON challenge_attempts (user_id);
