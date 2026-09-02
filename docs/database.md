# VOIDSTRIKE ARENA — PostgreSQL Database Schema Documentation

## 1. Overview & Connection Architecture

The database is built on **PostgreSQL 17** using strict referential integrity, indexed foreign keys, and normalized domain entities.

- **Connection**: Native PDO with `pgsql` driver.
- **Transactions**: Atomic transactions utilized for critical paths (match completion + XP leveling + challenge attempts).
- **Security**: 100% prepared parameterized queries (`:param`), zero string concatenation in SQL.

---

## 2. Table Catalog

### 2.1 `users`
Core pilot entity storing identity, credentials, career metrics, and personalization.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | SERIAL | PRIMARY KEY | Unique integer pilot ID |
| `username` | VARCHAR(50) | UNIQUE, NOT NULL | Alphanumeric pilot callsign |
| `email` | VARCHAR(190) | UNIQUE, NOT NULL | Contact & recovery email address |
| `password_hash` | VARCHAR(255) | NOT NULL | Argon2id cryptographic password hash |
| `display_name` | VARCHAR(100) | NOT NULL | Human-readable public display callsign |
| `avatar_url` | VARCHAR(255) | NULL | Relative path to uploaded insignia or SVG |
| `role` | VARCHAR(20) | NOT NULL DEFAULT 'player' | Access role (`player`, `moderator`, `admin`) |
| `status` | VARCHAR(20) | NOT NULL DEFAULT 'active' | Account state (`active`, `suspended`, `banned`) |
| `email_verified_at`| TIMESTAMP | NULL | Email verification timestamp |
| `xp` | INTEGER | NOT NULL DEFAULT 0 | Cumulative career experience points |
| `level` | INTEGER | NOT NULL DEFAULT 1 | Current level derived from XP formula |
| `selected_vehicle` | VARCHAR(50) | NOT NULL DEFAULT 'striker'| Preferred chassis (`striker`, `titan`, `phantom`) |
| `preferred_locale` | VARCHAR(10) | NOT NULL DEFAULT 'en' | Preferred dictionary (`en`, `fr`) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Registration timestamp |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last modification timestamp |

---

### 2.2 `run_tokens`
Cryptographic handshake tokens granting authorization to launch matches.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | SERIAL | PRIMARY KEY | Unique token sequence ID |
| `token_hash` | VARCHAR(64) | UNIQUE, NOT NULL | SHA-256 hash of the client's bearer token |
| `user_id` | INTEGER | NULL, REFERENCES users | Associated pilot (null for guests) |
| `vehicle_class` | VARCHAR(50) | NOT NULL | Authorized combat chassis |
| `arena_id` | VARCHAR(50) | NOT NULL | Authorized arena sector |
| `difficulty` | VARCHAR(20) | NOT NULL | Selected difficulty tier |
| `mode` | VARCHAR(20) | NOT NULL DEFAULT 'quick' | Game mode (`quick`, `challenge`, `rival`) |
| `challenge_id` | VARCHAR(36) | NULL | Associated challenge duel ID |
| `seed` | VARCHAR(64) | NOT NULL | Deterministic RNG seed |
| `expires_at` | TIMESTAMP | NOT NULL | Expiry threshold (typically 10 minutes) |
| `used_at` | TIMESTAMP | NULL | Single-use redemption timestamp |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Server issuance timestamp |

---

### 2.3 `matches`
Audited combat session records evaluated by server-side anti-cheat heuristics.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | VARCHAR(36) | PRIMARY KEY | Unique match identifier (`m_...`) |
| `user_id` | INTEGER | NULL, REFERENCES users | Participating pilot ID |
| `vehicle_class` | VARCHAR(50) | NOT NULL | Chassis used in match |
| `arena_id` | VARCHAR(50) | NOT NULL | Sector traversed |
| `difficulty` | VARCHAR(20) | NOT NULL | Combat intensity |
| `mode` | VARCHAR(20) | NOT NULL DEFAULT 'quick' | Match mode |
| `score` | INTEGER | NOT NULL DEFAULT 0 | Total combat score achieved |
| `waves_cleared` | INTEGER | NOT NULL DEFAULT 0 | Enemy waves eliminated |
| `kills` | INTEGER | NOT NULL DEFAULT 0 | Total drones destroyed |
| `accuracy` | NUMERIC(5, 2)| NOT NULL DEFAULT 0.00 | Weapon hit accuracy percentage |
| `combo_max` | INTEGER | NOT NULL DEFAULT 0 | Highest combo multiplier achieved |
| `duration_seconds`| INTEGER | NOT NULL DEFAULT 0 | Match duration in wall-clock seconds |
| `status` | VARCHAR(20) | NOT NULL DEFAULT 'completed' | Integrity state (`completed`, `flagged`, `invalidated`) |
| `anti_cheat_flags`| JSONB | NULL | Array of anomaly codes raised by engine |
| `run_token_hash` | VARCHAR(64) | NULL | Link to original run token |
| `start_nonce` | VARCHAR(64) | NULL | Verified client challenge nonce |
| `telemetry_summary`| JSONB | NULL | Aggregate combat metrics |
| `ghost_data` | JSONB | NULL | Compressed position checkpoints |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| `finished_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Concluded timestamp |

---

### 2.4 `challenges`
Community broadcast duel challenges with target score benchmarks.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | VARCHAR(36) | PRIMARY KEY | Unique challenge ID (`c_...`) |
| `creator_id` | INTEGER | NOT NULL, REFERENCES users | Host pilot who broadcast challenge |
| `target_score` | INTEGER | NOT NULL | Score required to beat the gauntlet |
| `vehicle_class` | VARCHAR(50) | NOT NULL | Mandatory chassis class |
| `arena_id` | VARCHAR(50) | NOT NULL | Mandatory arena sector |
| `difficulty` | VARCHAR(20) | NOT NULL | Mandatory combat tier |
| `expires_at` | TIMESTAMP | NOT NULL | Challenge expiration date |
| `challenger_count`| INTEGER | NOT NULL DEFAULT 0 | Number of pilots who attempted duel |
| `best_attempt_id` | INTEGER | NULL | Pointer to highest scoring attempt |
| `status` | VARCHAR(20) | NOT NULL DEFAULT 'active' | Status (`active`, `expired`, `completed`) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

---

### 2.5 `challenge_attempts`
Individual pilot attempts to conquer active community challenges.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | SERIAL | PRIMARY KEY | Unique attempt sequence ID |
| `challenge_id` | VARCHAR(36) | NOT NULL, REFERENCES challenges | Target challenge |
| `user_id` | INTEGER | NOT NULL, REFERENCES users | Attempting pilot |
| `match_id` | VARCHAR(36) | NOT NULL, REFERENCES matches | Associated audited match record |
| `score` | INTEGER | NOT NULL | Score achieved during attempt |
| `achieved_target`| BOOLEAN | NOT NULL DEFAULT FALSE | True if score $\ge$ target score |
| `waves` | INTEGER | NOT NULL DEFAULT 0 | Waves cleared |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp of attempt |

---

### 2.6 `achievements` & `user_achievements`
Gamification awards and accolade milestones unlocked across gameplay.

---

### 2.7 `notifications`
In-app communication ledger transmitting alerts, challenge updates, and unlock notices.

---

### 2.8 `admin_audit_logs`
Append-only tamper-evident security ledger logging administrative actions.
