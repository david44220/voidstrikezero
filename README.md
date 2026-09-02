# VOIDSTRIKE ARENA

> **High-Velocity Pure PHP 8.5 Full-Stack 3D Competitive Gaming Platform**  
> *Zero PHP Frameworks • Native PostgreSQL 17 • Procedural Three.js WebGL Engine • Deterministic Server-Side Anti-Cheat*

---

## 1. Executive Summary

**VOIDSTRIKE ARENA** is an online 3D competitive browser gaming platform built entirely from scratch without using Laravel, Symfony, or any full-stack web framework.

It demonstrates production-grade software engineering across the entire modern web stack:
- **Pure PHP 8.5+ Architecture**: Strict typing (`declare(strict_types=1)`), custom regex router, PSR-style middleware pipeline, thread-safe rate limiter, timing-safe CSRF protection, and Argon2id password hashing.
- **PostgreSQL 17 Backend**: Fully normalized relational schema across 8 migration files, strictly prepared PDO queries, atomic transactions, and automated seeders.
- **Procedural 3D WebGL Engine**: Native Three.js without external build tools or CDNs (100% offline self-contained), procedural geometries, Web Audio API procedural sound synthesizer, dynamic lighting, and particle shockwaves.
- **Tactical Combat AI**: Multi-tiered drone opponents (Scout Drone, Assault Mech, Enforcer Heavy) driven by an autonomous Finite State Machine (FSM) across Recruit, Veteran, and Void Master difficulties.
- **Deterministic Server-Side Anti-Cheat**: Cryptographic run authorization tokens, server-clock drift auditing, theoretical score rate ceilings, kill cadence limits, and telemetry sequence verification.
- **Complete Feature Matrix**: Pilot progression (Level & XP curve), Global & Weekly Leaderboards, Asynchronous Duel Challenges, In-Game Accolades/Achievements, Admin Security Nexus, and 100% Bilingual localization (English & French).

---

## 2. Technology Stack & Prerequisites

| Layer | Technology | Specification |
| :--- | :--- | :--- |
| **Runtime** | PHP | 8.5+ (CLI & Web Server) with `pdo_pgsql`, `sodium`, `mbstring`, `fileinfo` |
| **Database** | PostgreSQL | 16 / 17 on port `5432` (`voidstrike` database) |
| **Frontend** | Vanilla JS / CSS3 | ES Modules, CSS Custom Properties, Responsive Viewport, Web Audio API |
| **3D Engine** | Three.js | Local ES Module build (`public/assets/vendor/three/`) |
| **PWA** | Service Worker | Cache-First for static assets, Network-First for dynamic endpoints |

---

## 3. Quickstart & Deployment

### Step 1: Database Setup & Migration
Verify that PostgreSQL is running on `127.0.0.1:5432`. Configure your `.env` file (copied from `.env.example`):

```bash
cp .env.example .env
```

Run the automated migration runner to create the 8 tables and indexes:
```bash
php database/migrate.php
```

Seed the platform with initial achievements, administrator account, competitive pilots, and ranked records:
```bash
php database/seed.php
```

### Step 2: Run the Automated Test Suite
Execute the pure PHP test harness:
```bash
php tests/run.php
```
*Outputs 21 automated unit and integration tests across 73 assertions.*

### Step 3: Launch Local Web Server
Start the built-in PHP development server targeting the `public/` directory:
```bash
php -S 127.0.0.1:8000 -t public
```

Open your browser and navigate to:
**`http://127.0.0.1:8000`**

---

## 4. Default Seeded Credentials

### Administrator Nexus Account
- **Callsign**: `admin`
- **Email**: `admin@voidstrike.io`
- **Passcode**: `AdminPassword2026!`
- **Access**: Full platform control (`/admin`) to ban/unban pilots, inspect telemetry, and invalidate fraudulent match scores.

### Competitive Player Accounts
All player passwords are: `Password123!`

| Callsign | Display Name | Level | Default Chassis |
| :--- | :--- | :--- | :--- |
| `VortexBlade` | Commander Thorne | 12 | Striker |
| `ObsidianAegis` | Iron Valkyrie | 10 | Titan |
| `GhostRider99` | Phantom Specter | 8 | Phantom |
| `CyberPulse` | Cyber Pulse | 6 | Striker |
| `NeonViper` | Neon Viper | 5 | Striker |

---

## 5. Controls & Gameplay Guide

### Desktop Controls
- **W, A, S, D / Arrow Keys**: Thruster Navigation (Steering)
- **Mouse Pointer**: Directional Targeting (Raycasted to XZ Plane)
- **Left Mouse Click / J**: Primary Energy Weapon Fire
- **Spacebar**: Evasive Thruster Dash (Grants 0.35s invulnerability)
- **E / Right Mouse Click / K**: Chassis Special Ability (Overdrive / Kinetic Dome / Phase Shift)
- **Left Shift**: Overdrive Booster (Consumes capacitor energy)
- **Escape / P**: Tactical Pause

### Mobile & Touch Controls
- **Left On-Screen Analog Thumbstick**: Directional steering with boundary constraints.
- **Right Action Buttons**: Fire, Dash, and Special Ability triggers.
- **Touch Gestures**: Viewport locked with `touch-action: none` to eliminate accidental browser scrolling or zoom during combat.

---

## 6. Architecture & Directory Overview

```text
├── app/
│   ├── Achievements/       # Accolade evaluation and unlocking engine
│   ├── Admin/              # Admin security nexus, moderation, and audit logger
│   ├── Auth/               # Argon2id authentication service and route guards
│   ├── Challenges/         # Asynchronous player duel challenge repository
│   ├── Core/               # Frameworkless core: Router, Request, Response, Database, View, Session, RateLimiter
│   ├── Game/               # Server-side AntiCheatValidator and MatchEngine lifecycle
│   ├── Leaderboard/        # Global & Weekly rankings queries
│   ├── Localization/       # Translator and Locale detection middleware
│   ├── Matches/            # Match database repository and stats aggregation
│   ├── Notifications/      # In-app player alerts and notifications service
│   ├── Security/           # CSRF protection and strict security headers middleware
│   └── Users/              # User entity, XP leveling formula, avatar generation
├── config/                 # Modular configuration files (app, database, security, game)
├── database/
│   ├── migrations/         # 8 Normalized SQL migration files
│   ├── migrate.php         # Migration runner
│   └── seed.php            # Seeder for achievements, users, matches, challenges
├── docs/                   # Detailed documentation (architecture, security, anticheat, database)
├── public/                 # Public web root
│   ├── assets/
│   │   ├── css/style.css   # Dark sci-fi cyberpunk responsive stylesheet
│   │   ├── js/             # app.js and Three.js game engine modules
│   │   └── vendor/three/   # Self-contained local Three.js ES build
│   ├── index.php           # Front controller entry point
│   ├── manifest.json       # PWA web manifest
│   └── service-worker.js   # Progressive web app caching service worker
├── resources/
│   ├── lang/               # en.php and fr.php comprehensive translation dictionaries
│   └── views/              # Semantic HTML5 view templates with layout inheritance
├── routes/                 # Web and API routing tables
└── tests/                  # Automated unit and integration test suites + test runner
```

---

## 7. License & Attribution

Built for the **VOIDSTRIKE ARENA Full-Stack Benchmark**.  
Zero external proprietary frameworks. Released under the MIT License.
