<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Game\AntiCheatValidator;
use Tests\TestCase;

class AntiCheatValidatorTest extends TestCase
{
    public function testLegitimatePayloadPasses(): void
    {
        $tokenRecord = [
            'vehicle_class' => 'striker',
            'difficulty' => 'normal',
        ];

        $payload = [
            'start_nonce' => 'valid_nonce_123',
            'duration' => 60,
            'score' => 8000,
            'waves' => 3,
            'kills' => 12,
            'combo_max' => 3,
            'telemetry' => [
                ['time' => 10, 'score' => 2000, 'hull' => 80],
                ['time' => 30, 'score' => 4500, 'hull' => 65],
                ['time' => 60, 'score' => 8000, 'hull' => 50],
            ],
        ];

        // Server measured 61 seconds (1s drift within 15s tolerance)
        $result = AntiCheatValidator::validate($tokenRecord, $payload, 61.0);

        $this->assertTrue($result['valid'], 'Legitimate payload should be valid.');
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(0, count($result['flags']));
    }

    public function testExcessiveScoreRateFlagged(): void
    {
        $tokenRecord = [
            'vehicle_class' => 'striker',
            'difficulty' => 'normal',
        ];

        // 30 seconds duration, but 80,000 score = 2666 pts/sec (ceiling is 280)
        $payload = [
            'start_nonce' => 'valid_nonce_123',
            'duration' => 30,
            'score' => 80000,
            'waves' => 2,
            'kills' => 10,
            'combo_max' => 2,
            'telemetry' => [],
        ];

        $result = AntiCheatValidator::validate($tokenRecord, $payload, 30.0);

        $this->assertFalse($result['valid'], 'Excessive score rate must be flagged.');
        $this->assertEquals('invalidated', $result['status']);
        $this->assertStringContains('IMPOSSIBLE_SCORE_RATE', implode(',', $result['flags']));
    }

    public function testDurationTooShortFlagged(): void
    {
        $tokenRecord = [
            'vehicle_class' => 'striker',
            'difficulty' => 'normal',
        ];

        // 5 seconds duration (min required is 25s)
        $payload = [
            'start_nonce' => 'valid_nonce_123',
            'duration' => 5,
            'score' => 500,
            'waves' => 1,
            'kills' => 2,
            'combo_max' => 1,
            'telemetry' => [],
        ];

        $result = AntiCheatValidator::validate($tokenRecord, $payload, 5.0);

        $this->assertFalse($result['valid']);
        $this->assertStringContains('MATCH_TOO_SHORT', implode(',', $result['flags']));
    }

    public function testExcessiveComboFlagged(): void
    {
        $tokenRecord = [
            'vehicle_class' => 'striker',
            'difficulty' => 'normal',
        ];

        // Combo 12 exceeds max cap 5
        $payload = [
            'start_nonce' => 'valid_nonce_123',
            'duration' => 50,
            'score' => 5000,
            'waves' => 2,
            'kills' => 8,
            'combo_max' => 12,
            'telemetry' => [],
        ];

        $result = AntiCheatValidator::validate($tokenRecord, $payload, 50.0);

        $this->assertFalse($result['valid']);
        $this->assertStringContains('COMBO_CEILING_EXCEEDED', implode(',', $result['flags']));
    }

    public function testKillCadenceExceededFlagged(): void
    {
        $tokenRecord = [
            'vehicle_class' => 'striker',
            'difficulty' => 'normal',
        ];

        // 60 kills in 30 seconds = 2.0 kills/sec (max capacity is 1.8 kills/sec)
        $payload = [
            'start_nonce' => 'valid_nonce_123',
            'duration' => 30,
            'score' => 5000,
            'waves' => 2,
            'kills' => 60,
            'combo_max' => 2,
            'telemetry' => [],
        ];

        $result = AntiCheatValidator::validate($tokenRecord, $payload, 30.0);

        $this->assertFalse($result['valid']);
        $this->assertStringContains('IMPOSSIBLE_KILL_CADENCE', implode(',', $result['flags']));
    }

    public function testNonMonotonicTelemetryFlagged(): void
    {
        $tokenRecord = [
            'vehicle_class' => 'striker',
            'difficulty' => 'normal',
        ];

        // Telemetry score drops from 4000 to 2000
        $payload = [
            'start_nonce' => 'valid_nonce_123',
            'duration' => 60,
            'score' => 5000,
            'waves' => 2,
            'kills' => 6,
            'combo_max' => 2,
            'telemetry' => [
                ['time' => 10, 'score' => 1000, 'hull' => 80],
                ['time' => 20, 'score' => 4000, 'hull' => 80],
                ['time' => 30, 'score' => 2000, 'hull' => 80], // Decreased!
                ['time' => 40, 'score' => 5000, 'hull' => 80],
            ],
        ];

        $result = AntiCheatValidator::validate($tokenRecord, $payload, 60.0);

        $this->assertFalse($result['valid']);
        $this->assertStringContains('TELEMETRY_NON_MONOTONIC_SCORE', implode(',', $result['flags']));
    }
}
