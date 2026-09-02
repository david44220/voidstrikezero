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

        // 2. Score Rate Sanity Check
        $effectiveDuration = max(1, $duration);
        $scorePerSecond = $score / $effectiveDuration;
        $maxScorePerSec = (float) ($cfg['max_score_per_second'] ?? 280) * ($diffCfg['score_multiplier'] ?? 1.0);

        if ($scorePerSecond > $maxScorePerSec) {
            $flags[] = sprintf("IMPOSSIBLE_SCORE_RATE: %.1f pts/sec exceeds theoretical maximum ceiling %.1f pts/sec", $scorePerSecond, $maxScorePerSec);
            $riskScore += 80;
        }

        // 3. Absolute Score Bounds
        if ($score < 0) {
            $flags[] = "NEGATIVE_SCORE: Score cannot be negative";
            $riskScore += 100;
        }

        if ($score > 1000000) {
            $flags[] = "EXCESSIVE_SCORE: Score exceeds absolute hard ceiling (1,000,000 pts)";
            $riskScore += 100;
        }

        // 4. Kill Rate Sanity Check
        $killsPerSecond = $kills / $effectiveDuration;
        $maxKillsPerSec = (float) ($cfg['max_kills_per_second'] ?? 1.8);

        if ($killsPerSecond > $maxKillsPerSec) {
            $flags[] = sprintf("IMPOSSIBLE_KILL_CADENCE: %.2f kills/sec exceeds maximum spawn frequency %.2f kills/sec", $killsPerSecond, $maxKillsPerSec);
            $riskScore += 70;
        }

        // 5. Waves vs Kills Ratio
        // Each wave spawns at least 3-6 drones
        if ($waves > 0 && ($kills / $waves) > 25) {
            $flags[] = sprintf("KILL_WAVE_INCONSISTENCY: %d kills over %d waves exceeds wave spawn capacity", $kills, $waves);
            $riskScore += 40;
        }

        // 6. Combo Multiplier Ceiling
        $maxCombo = (int) ($cfg['max_combo_multiplier'] ?? 5);
        if ($comboMax > $maxCombo) {
            $flags[] = "COMBO_CEILING_EXCEEDED: Combo {$comboMax}x exceeds absolute engine limit {$maxCombo}x";
            $riskScore += 60;
        }

        // 7. Telemetry Sequence Analysis (if snapshots provided)
        if (!empty($telemetry)) {
            $prevScore = 0;
            $maxRecordedHull = 0;
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
