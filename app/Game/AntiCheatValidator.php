<?php

declare(strict_types=1);

namespace App\Game;

class AntiCheatValidator
{
    public static function validate(array $tokenRecord, array $data, float $serverElapsedSeconds): array
    {
        $flags = [];
        $riskScore = 0;
        $cfg = config('game.anti_cheat');

        $score = (int) ($data['score'] ?? 0);
        $duration = (int) ($data['duration'] ?? 0);
        $kills = (int) ($data['kills'] ?? 0);
        $waves = (int) ($data['waves'] ?? 0);
        $comboMax = (int) ($data['combo_max'] ?? 1);
        $vehicle = $data['vehicle'] ?? $tokenRecord['vehicle_class'];
        $difficulty = $data['difficulty'] ?? $tokenRecord['difficulty'];
        $telemetry = is_array($data['telemetry'] ?? null) ? $data['telemetry'] : [];

        $diffCfg = config("game.difficulties.{$difficulty}", config('game.difficulties.normal'));
        $vehicleCfg = config("game.vehicles.{$vehicle}", config('game.vehicles.striker'));
        $diffMultiplier = (float) ($diffCfg['score_multiplier'] ?? 1.0);

        // 1. Check Duration Limits
        $minDuration = (int) ($cfg['min_match_duration'] ?? 25);
        $maxDuration = (int) ($cfg['max_match_duration'] ?? 360);
        $driftTol = (float) ($cfg['clock_drift_tolerance_seconds'] ?? 15);

        if ($duration < $minDuration) {
            $flags[] = "MATCH_TOO_SHORT: Duration ({$duration}s) below minimal legitimate combat threshold ({$minDuration}s)";
            $riskScore += 50;
        }

        if ($duration > $maxDuration) {
            $flags[] = "MATCH_EXCEEDS_MAX_DURATION: Duration ({$duration}s) exceeds operational ceiling ({$maxDuration}s)";
            $riskScore += 20;
        }

        // Check clock drift against server elapsed time
        $drift = abs($serverElapsedSeconds - $duration);
        if ($drift > $driftTol) {
            $flags[] = sprintf("CLOCK_DRIFT_EXCEEDED: Reported duration (%ds) drifts by %.1fs from server clock (%.1fs)", $duration, $drift, $serverElapsedSeconds);
            $riskScore += 45;
        }

        // 2. Start Nonce Validation
        $clientNonce = (string) ($data['start_nonce'] ?? '');
        $expectedNonce = (string) ($tokenRecord['start_nonce'] ?? '');
        if ($expectedNonce !== '' && (!hash_equals($expectedNonce, $clientNonce))) {
            $flags[] = "START_NONCE_MISMATCH: Cryptographic start nonce mismatch";
            $riskScore += 80;
        }

        // 3. Score Rate Sanity Check
        $effectiveDuration = max(1, $duration);
        $scorePerSecond = $score / $effectiveDuration;
        $maxScorePerSec = (float) ($cfg['max_score_per_second'] ?? 280) * $diffMultiplier;

        if ($scorePerSecond > $maxScorePerSec) {
            $flags[] = sprintf("IMPOSSIBLE_SCORE_RATE: %.1f pts/sec exceeds theoretical maximum ceiling %.1f pts/sec", $scorePerSecond, $maxScorePerSec);
            $riskScore += 80;
        }

        // 4. Absolute Score Bounds
        if ($score < 0) {
            $flags[] = "NEGATIVE_SCORE: Score cannot be negative";
            $riskScore += 100;
        }

        if ($score > 1000000) {
            $flags[] = "EXCESSIVE_SCORE: Score exceeds absolute hard ceiling (1,000,000 pts)";
            $riskScore += 100;
        }

        // 5. Kill Rate Sanity Check
        $killsPerSecond = $kills / $effectiveDuration;
        $maxKillsPerSec = (float) ($cfg['max_kills_per_second'] ?? 1.8);

        if ($killsPerSecond > $maxKillsPerSec) {
            $flags[] = sprintf("IMPOSSIBLE_KILL_CADENCE: %.2f kills/sec exceeds maximum spawn frequency %.2f kills/sec", $killsPerSecond, $maxKillsPerSec);
            $riskScore += 70;
        }

        // 6. Wave Sanity & Duration Cross-Checks
        if ($waves > 0) {
            // Each wave requires at least 6 seconds to spawn and defeat
            $minWaveDuration = $waves * 6;
            if ($duration < $minWaveDuration) {
                $flags[] = sprintf("WAVE_DURATION_ANOMALY: %d waves claimed in only %ds (minimum %ds required)", $waves, $duration, $minWaveDuration);
                $riskScore += 55;
            }

            // Each wave requires at least 1 kill to complete
            if ($kills < $waves) {
                $flags[] = sprintf("KILL_WAVE_DEFICIT: %d kills cannot clear %d waves", $kills, $waves);
                $riskScore += 50;
            }

            // Waves vs Kills Ratio (maximum 25 drones per wave)
            if (($kills / $waves) > 25) {
                $flags[] = sprintf("KILL_WAVE_INCONSISTENCY: %d kills over %d waves exceeds wave spawn capacity", $kills, $waves);
                $riskScore += 40;
            }
        }

        // 7. Theoretical Combat Score Ceiling (kills * max_points + wave_bonuses)
        $waveBonusSum = 0;
        for ($w = 1; $w <= $waves; $w++) {
            $waveBonusSum += (1500 * $w);
        }
        $maxTheoreticalScore = ($kills * 600 * 5.0) + ($waveBonusSum * $diffMultiplier) + 3000;
        if ($score > $maxTheoreticalScore) {
            $flags[] = sprintf("SCORE_EXCEEDS_COMBAT_CEILING: Score %d exceeds combat ceiling %d pts for %d kills / %d waves", $score, (int) $maxTheoreticalScore, $kills, $waves);
            $riskScore += 75;
        }

        // 8. Combo Multiplier Ceiling
        $maxCombo = (int) ($cfg['max_combo_multiplier'] ?? 5);
        if ($comboMax > $maxCombo) {
            $flags[] = "COMBO_CEILING_EXCEEDED: Combo {$comboMax}x exceeds absolute engine limit {$maxCombo}x";
            $riskScore += 60;
        }

        // 9. Combat Telemetry Cross-Checks (Ballistics & Damage)
        $shotsFired = (int) ($data['shots_fired'] ?? 0);
        $shotsHit = (int) ($data['shots_hit'] ?? 0);
        $damageDealt = (int) ($data['damage_dealt'] ?? 0);
        $damageTaken = (int) ($data['damage_taken'] ?? 0);

        if ($shotsFired > 0 && $shotsHit > $shotsFired) {
            $flags[] = "SHOTS_INCONSISTENCY: Shots hit ({$shotsHit}) cannot exceed shots fired ({$shotsFired})";
            $riskScore += 60;
        }

        if ($kills > 0 && $shotsFired > 0 && $shotsHit < $kills) {
            $flags[] = "SHOTS_KILL_DEFICIT: Shots hit ({$shotsHit}) insufficient for {$kills} confirmed kills";
            $riskScore += 45;
        }

        if ($shotsHit > 0 && $damageDealt > ($shotsHit * 200 * $maxCombo)) {
            $flags[] = "DAMAGE_DEALT_EXCEEDS_BALLISTIC_CEILING: Damage dealt exceeds ballistic limits";
            $riskScore += 55;
        }

        // 10. Telemetry Stream Validation (Required for runs >= minDuration)
        if ($duration >= $minDuration) {
            if (empty($telemetry) || count($telemetry) < 3) {
                $flags[] = "TELEMETRY_MISSING_OR_INSUFFICIENT: Match duration ({$duration}s) requires coherent telemetry snapshots";
                $riskScore += 45;
            }
        }

        if (!empty($telemetry)) {
            $prevScore = 0;
            $maxChassisHull = (int) ($vehicleCfg['max_health'] ?? 100);

            foreach ($telemetry as $index => $tick) {
                $tickScore = (int) ($tick['score'] ?? 0);
                $tickHull = (int) ($tick['hull'] ?? 0);

                if ($tickScore < $prevScore) {
                    $flags[] = "TELEMETRY_NON_MONOTONIC_SCORE: Score decreased at tick index {$index}";
                    $riskScore += 50;
                    break;
                }
                $prevScore = $tickScore;

                if ($tickHull > ($maxChassisHull * 1.5)) {
                    $flags[] = "TELEMETRY_HULL_ANOMALY: Hull ({$tickHull}) exceeds maximum chassis capacity ({$maxChassisHull})";
                    $riskScore += 60;
                    break;
                }
            }
        }

        // Final Assessment
        $status = 'completed';
        $valid = true;

        if ($riskScore >= 70) {
            $status = 'invalidated';
            $valid = false;
        } elseif ($riskScore >= 30) {
            $status = 'flagged';
            $valid = false;
        }

        return [
            'valid' => $valid,
            'status' => $status,
            'risk_score' => min(100, $riskScore),
            'flags' => $flags,
            'server_elapsed_seconds' => round($serverElapsedSeconds, 2),
            'reported_duration' => $duration,
            'score_rate' => round($scorePerSecond, 2),
        ];
    }
}
