# VOIDSTRIKE ARENA — Server-Side Anti-Cheat Specification

## 1. Threat Model & Overview

In web-based competitive gaming, client-side integrity cannot be trusted. Vulnerabilities commonly targeted by adversarial actors include:
1. **Direct API Spoofing**: Fabricating HTTP POST requests claiming astronomical scores without ever running the game.
2. **Replay Attacks**: Re-transmitting a previously accepted valid run payload to multiply ranking gains.
3. **Session / Context Hijacking**: Fulfilling a guest run or third-party token to claim authenticated XP and accolades.
4. **Speed Hacks & Teleportation**: Artificially accelerating the client clock to defeat waves instantaneously.
5. **Memory Manipulation**: Injecting arbitrary score or multiplier variables into client memory.

VOIDSTRIKE ARENA defends against these attack vectors using a **multi-stage cryptographic, atomic, and heuristic verification pipeline**.

---

## 2. Stage 1: Cryptographic Handshake, Run Tokens & User Context Binding

A client cannot simply submit a score to `/api/match/finish`. Every combat engagement must be pre-authorized via `/api/match/start`:

```
[Client] ---> POST /api/match/start ---> [Server]
                                              |
                                              v
                                   1. Generate 256-bit cryptographic raw token
                                   2. Generate cryptographic 128-bit start_nonce
                                   3. Compute SHA-256 hash ($tokenHash)
                                   4. Bind user context (user_id and session_hash)
                                   5. Store in `run_tokens` table with:
                                      - token_hash
                                      - user_id (NULL for guest, integer for authenticated)
                                      - vehicle_class, arena_id, difficulty
                                      - start_nonce
                                      - session_hash
                                      - created_at = CURRENT_TIMESTAMP
                                      - expires_at = CURRENT_TIMESTAMP + 10 mins
                                      - used_at = NULL
                                              |
[Client] <--- Return run_token, start_nonce, & seed <---
```

### 2.1 User / Guest Context Enforcement
- **Guest Invariant**: If a token was issued to a guest (`user_id = NULL`), it **must remain a guest run**. Even if the finish submission is sent with an authenticated session, the server strictly forces `user = null`, preventing any XP or achievement awards.
- **Identity Invariant**: If a token was issued to an authenticated pilot (`user_id = X`), the finish request **must be authenticated as pilot X**. Any mismatch raises an immediate security exception.

### 2.2 Atomic Row-Locked Token Redemption
To prevent concurrent redemption race conditions, token consumption is executed within an atomic database transaction with PostgreSQL row locking:
```sql
SELECT * FROM run_tokens WHERE token_hash = :hash FOR UPDATE;
UPDATE run_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = :id AND used_at IS NULL;
```
If `rowCount() !== 1`, the transaction aborts with `"Run authorization token already redeemed"`. Exactly one finish request can redeem a token.

### 2.3 Challenge Integrity
When a run references a challenge, the server independently queries `challenges` where `status = 'active' AND expires_at > CURRENT_TIMESTAMP`. The server strictly overrides the chassis, arena, and difficulty to match the challenge configuration, ignoring client-supplied overrides.

---

## 3. Stage 2: Server-Clock Drift Auditing

Clients report a `duration` in seconds. However, the server independently measures the true elapsed wall-clock duration since the token was issued:

$$\Delta t_{server} = t_{now} - t_{token\_created}$$
$$\text{Drift} = |\Delta t_{server} - \text{duration}_{client}|$$

- If $\text{duration}_{client} < 25\text{s}$ (minimal legitimate combat threshold), the match is flagged for `MATCH_TOO_SHORT` (+50 risk).
- If $\text{Drift} > 15\text{s}$ (exceeding maximum permitted network latency and clock jitter), the match is flagged for `CLOCK_DRIFT_EXCEEDED` (+45 risk).

---

## 4. Stage 3: Combat Bounds, Kill Cadence & Telemetry Cross-Checks

### 4.1 Maximum Score Rate
$$\text{Rate}_{score} = \frac{\text{Score}}{\max(1, \text{Duration})}$$
$$\text{Rate}_{max} = 280\text{ pts/sec} \times \text{Difficulty Multiplier}$$
If $\text{Rate}_{score} > \text{Rate}_{max}$, flagged for `IMPOSSIBLE_SCORE_RATE` (+80 risk).

### 4.2 Maximum Kill Cadence
$$\text{Rate}_{kills} = \frac{\text{Kills}}{\max(1, \text{Duration})}$$
Enforces a physical ceiling of **1.8 kills/second**. If exceeded, flagged for `IMPOSSIBLE_KILL_CADENCE` (+70 risk).

### 4.3 Wave Duration & Kill Deficit
- Minimum 6 seconds required per cleared wave. If $\text{Duration} < \text{Waves} \times 6$, flagged for `WAVE_DURATION_ANOMALY` (+55 risk).
- Each cleared wave requires at least 1 kill. If $\text{Kills} < \text{Waves}$, flagged for `KILL_WAVE_DEFICIT` (+50 risk).

### 4.4 Theoretical Combat Score Ceiling
Based on physical drone points and wave clear bonuses under maximum 5.0x combo:
$$S_{max} = (\text{Kills} \times 600 \times 5.0) + \left(\sum_{w=1}^{\text{Waves}} 1500 \cdot w \cdot \text{DiffMult}\right) + 3000$$
If $\text{Score} > S_{max}$, flagged for `SCORE_EXCEEDS_COMBAT_CEILING` (+75 risk).

### 4.5 Telemetry Stream Validation
For matches lasting $\ge 25\text{s}$, a coherent telemetry stream of at least 3 periodic snapshots is required. If absent or too sparse, flagged for `TELEMETRY_MISSING_OR_INSUFFICIENT` (+45 risk).
- **Monotonicity**: Score must never decrease between snapshots (`TELEMETRY_NON_MONOTONIC_SCORE`, +50 risk).
- **Hull Boundary**: Hull cannot exceed $1.5 \times \text{MaxChassisHull}$ (`TELEMETRY_HULL_ANOMALY`, +60 risk).

---

## 5. Risk Scoring & Sanction Thresholds

The heuristics accumulate risk points. Matches are evaluated according to the following thresholds:

| Cumulative Risk Score | Match Outcome | Public Leaderboard Eligibility | Audit Logging |
| :--- | :--- | :--- | :--- |
| **0 – 29** | `completed` | Ranked on Global and Weekly matrices | Normal combat log |
| **30 – 69** | `flagged` | Excluded from rankings | Flagged for security review |
| **$\ge$ 70** | `invalidated` | Permanently purged | Security incident logged to audit trail |

Administrators have full visibility into flagged matches via `/admin/matches` and can manually inspect telemetry and enforce bans.
