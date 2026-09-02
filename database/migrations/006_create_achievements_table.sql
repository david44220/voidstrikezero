-- 006_create_achievements_table.sql

CREATE TABLE IF NOT EXISTS achievements (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name_en VARCHAR(100) NOT NULL,
    name_fr VARCHAR(100) NOT NULL,
    description_en VARCHAR(255) NOT NULL,
    description_fr VARCHAR(255) NOT NULL,
    icon VARCHAR(50) NOT NULL DEFAULT 'award',
    category VARCHAR(50) NOT NULL DEFAULT 'combat',
    xp_reward INTEGER NOT NULL DEFAULT 100,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_achievements (
    user_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    achievement_id INTEGER NOT NULL REFERENCES achievements (id) ON DELETE CASCADE,
    unlocked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, achievement_id)
);

CREATE INDEX IF NOT EXISTS idx_user_achievements_user ON user_achievements (user_id);
