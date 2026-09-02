# VOIDSTRIKE ARENA — System Architecture Documentation

## 1. Architectural Philosophy

VOIDSTRIKE ARENA is constructed upon a **Frameworkless Modular-Monolith** architecture. It rejects monolithic kitchen-sink frameworks (such as Laravel or Symfony) in favor of high-performance, strictly-typed pure PHP 8.5+.

### Core Tenets:
1. **Zero External Framework Dependencies**: The core HTTP abstractions, database layer, routing engine, and template engine are self-contained and zero-overhead.
2. **Deterministic Anti-Cheat Verification**: Matches cannot be submitted blindly. A pre-issued cryptographic run token bound to a server nonce must precede every match, and telemetry is rigorously analyzed.
3. **Offline Self-Sufficiency**: Three.js is vendored locally as clean ES modules without reliance on external CDNs or bundling compilers.
4. **Security by Default**: Strict Content Security Policy (CSP), timing-safe CSRF validation, file-locked atomic rate limiting, and Argon2id password hashing.

---

## 2. Core Engine Components

### 2.1 Front Controller (`public/index.php`)
Every HTTP request enters via `public/index.php`. The front controller initializes:
- `Env::load()`: Parses the `.env` file into runtime configuration.
- `Config::init()`: Reads configuration arrays with dot-notation querying.
- `Translator::init()`: Loads bilingual translation dictionaries.
- `View::init()`: Sets up the template rendering directory.
- `Session::getInstance()->start()`: Initializes secure HTTP-only cookies.
- `Router`: Dispatches through global and route-specific middleware pipelines.

### 2.2 Routing Engine (`app/Core/Router.php`)
The router utilizes compiled regular expressions to match HTTP verbs and path templates:
- Supports RESTful verbs (`GET`, `POST`, `PUT`, `DELETE`).
- Supports named parameters (e.g. `/challenge/{id}`).
- Supports route grouping with prefixing and nested middleware stacks.
- Dispatches to controller actions (`[Controller::class, 'method']`) or anonymous closures.

### 2.3 Middleware Pipeline
Middlewares wrap request handlers in an onion-layer pattern:
- **`SecurityHeadersMiddleware`**: Injects CSP (`default-src 'self'`), `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, and `Permissions-Policy`.
- **`LocaleMiddleware`**: Inspects query parameters (`?lang=fr`), session state, and `Accept-Language` headers to configure the active dictionary.
- **`CsrfMiddleware`**: Validates anti-forgery tokens for state-modifying requests (`POST`, `PUT`, `DELETE`), exempting signature-authorized API endpoints.
- **`RateLimitMiddleware`**: File-locked thread-safe rate limiter protecting sensitive actions (login, registration, match submissions).
- **`AuthMiddleware` & `AdminMiddleware`**: Guard authenticated and administrative sectors.

### 2.4 Database Abstraction Layer (`app/Core/Database.php`)
Singleton wrapper around PHP Data Objects (`PDO`) connecting to PostgreSQL 17:
- All queries strictly utilize parameterized prepared statements.
- Atomic transaction wrappers (`beginTransaction`, `commit`, `rollBack`).
- Helper methods for standardized CRUD operations (`select`, `selectOne`, `selectValue`, `insert`, `update`, `delete`).

---

## 3. Localization Architecture (`app/Localization/`)

Bilingual support (English & French) is natively integrated:
- Translation keys follow hierarchical dot-notation (e.g. `__('hero.title')`, `__('dashboard.level', ['level' => 5])`).
- Language switching persists in pilot session and synchronizes with user account preferences.
- Fallback mechanics ensure that missing keys in secondary languages seamlessly resolve to the primary English dictionary.

---

## 4. 3D WebGL Engine & Client Systems

The client engine (`public/assets/js/game/`) operates as a modular ES6 architecture:
1. **`Engine.js`**: Master render loop running at up to 60/120 Hz using `requestAnimationFrame`, coordinating scene graphs, cameras, and delta time clamping.
2. **`Vehicle.js`**: Procedurally models 3 distinct combat chassis (Striker, Titan, Phantom) with unique mass, acceleration, dash cooldowns, and special abilities.
3. **`Arena.js`**: Generates 3 environments (Neon Core, Orbital Station, Magma Foundry) complete with obstacle colliders, hazard damage zones, and atmospheric fog.
4. **`AI.js`**: Autonomous enemy drone FSM with dynamic behaviors (Patrol, Pursue, Strafe/Attack, Evade/Retreat to Health pickups).
5. **`Audio.js`**: Procedural Web Audio API sound synthesizer producing lasers, explosions, dash whooshes, and hit alarms with zero external audio assets.
6. **`Input.js` & `TouchControls.js`**: Unified input abstraction translating keyboard/mouse, Gamepad API, and on-screen virtual analog joysticks into standardized motion vectors.
