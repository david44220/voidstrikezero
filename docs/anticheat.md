# VOIDSTRIKE ARENA — Server-Side Anti-Cheat Specification

## 1. Threat Model & Overview

In web-based competitive gaming, client-side integrity cannot be trusted. Vulnerabilities commonly targeted by adversarial actors include:
1. **Direct API Spoofing**: Fabricating HTTP POST requests claiming astronomical scores without ever running the game.
2. **Replay Attacks**: Re-transmitting a previously accepted valid run payload to multiply ranking gains.
3. **Speed Hacks & Teleportation**: Artificially accelerating the client clock to defeat waves instantaneously.
4. **Memory Manipulation**: Injecting arbitrary score or multiplier variables into memory.

VOIDSTRIKE ARENA defends against these attack vectors using a **multi-stage cryptographic and heuristic verification pipeline**.

---

## 2. Stage 1: Cryptographic Handshake & Run Tokens

A client cannot simply submit a score to `/api/match/finish`. Every combat engagement must be pre-authorized:

```
[Client] ---> POST /api/match/start ---> [Server]
                                              |
                                              v
                                   1. Generate 256-bit cryptographically secure raw token
                                   2. Compute SHA-256 hash ($tokenHash)
                                   3. Store record in `run_tokens` table with:
                                      - token_hash
                                      - user_id
                                      - vehicle_class, arena_id, difficulty
                                      - start_nonce
                                      - created_at = CURRENT_TIMESTAMP
                                      - expires_at = CURRENT_TIMESTAMP + 10 mins
                                      - used_at = NULL
                                              |
[Client] <--- Return run_token & nonce <-------
```

### Replay Prevention
When a match concludes, the server updates `run_tokens.used_at = CURRENT_TIMESTAMP`. If any subsequent request presents the same `run_token`, the server throws an immediate exception:
```php
if ($tokenRecord['used_at'] !== null) {
    throw new Exception("Run authorization token already redeemed");
}
```

---

## 3. Stage 2: Server-Clock Drift Auditing

Clients report a `duration` in seconds. However, the server independently measures the true elapsed wall-clock duration since the token was issued:

$$\Delta t_{server} = t_{now} - t_{token\_created}$$
$$\text{Drift} = |\Delta t_{server} - \text{duration}_{client}|$$

- If $\text{duration}_{client} < 25\text{s}$ (the minimal threshold for legitimate wave combat), the match is flagged for `MATCH_TOO_SHORT`.
- If $\text{Drift} > 15\text{s}$ (exceeding maximum permitted network latency and clock jitter), the match is flagged for `CLOCK_DRIFT_EXCEEDED`.

---

## 4. Stage 3: Theoretical Score Rate & Kill Cadence Bounds

The game engine possesses fixed physical ceilings regarding how rapidly enemies can spawn and yield points:

### 4.1 Maximum Score Rate
$$\text{Rate}_{score} = \frac{\text{Score}}{\max(1, \text{Duration})}$$

Under maximum possible enemy spawn density and a maximum 5.0x combo multiplier, the absolute theoretical ceiling is:
$$\text{Rate}_{max} = 280\text{ pts/sec} \times \text{Difficulty Multiplier}$$

If $\text{Rate}_{score} > \text{Rate}_{max}$, the match is flagged with `IMPOSSIBLE_SCORE_RATE` (+80 risk points).

### 4.2 Maximum Kill Cadence
$$\text{Rate}_{kills} = \frac{\text{Kills}}{\max(1, \text{Duration})}$$

The physical weapon cooldowns and enemy spawn pools enforce a hard limit of **1.8 kills/second**. Any payload reporting a higher kill density is flagged with `IMPOSSIBLE_KILL_CADENCE` (+70 risk points).

---

## 5. Stage 4: Monotonic Telemetry Continuity

Throughout the match, the client buffers periodic telemetry snapshots (every 3 seconds) containing `{ time, score, hull, energy }`:
- **Monotonicity Rule**: Score must be non-decreasing:
  $$\text{Score}_{k+1} \ge \text{Score}_k$$
  Any backward jump triggers `TELEMETRY_NON_MONOTONIC_SCORE`.
- **Chassis Bounds**: Recorded hull value cannot exceed the vehicle class's maximum health plus nanite overheal capacity ($1.5 \times \text{MaxHull}$). Excess values trigger `TELEMETRY_HULL_ANOMALY`.

---

## 6. Risk Scoring & Sanction Matrix

| Cumulative Risk Score | Match Outcome | Public Leaderboard Eligibility | Audit Logging |
| :--- | :--- | :--- | :--- |
| **0 – 34** | `completed` | Ranked & displayed publicly | Normal combat log |
| **35 – 74** | `flagged` | Excluded from rankings | Flagged for administrator review |
| **$\ge$ 75** | `invalidated` | Permanently purged | Security incident logged to audit trail |

Administrators have full visibility into flagged matches via `/admin/matches` and can manually inspect telemetry and enforce bans.
