-- 001_create_users_table.sql

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    avatar_url VARCHAR(255) NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'player',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    xp INTEGER NOT NULL DEFAULT 0,
    level INTEGER NOT NULL DEFAULT 1,
    selected_vehicle VARCHAR(50) NOT NULL DEFAULT 'striker',
    preferred_locale VARCHAR(10) NOT NULL DEFAULT 'en',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_username ON users (LOWER(username));
CREATE INDEX IF NOT EXISTS idx_users_email ON users (LOWER(email));
CREATE INDEX IF NOT EXISTS idx_users_xp ON users (xp DESC);
CREATE INDEX IF NOT EXISTS idx_users_role_status ON users (role, status);
