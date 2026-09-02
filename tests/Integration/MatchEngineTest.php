<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Game\MatchEngine;
use App\Users\User;
use Tests\TestCase;

class MatchEngineTest extends TestCase
{
    public function testLevelProgressionMath(): void
    {
        $user = new User(['xp' => 0, 'level' => 1]);

        $this->assertEquals(1, $user->calculateLevel(0));
        $this->assertEquals(2, $user->calculateLevel(100));
        $this->assertEquals(3, $user->calculateLevel(400));
        $this->assertEquals(4, $user->calculateLevel(900));
        $this->assertEquals(11, $user->calculateLevel(10000));
    }

    public function testMatchLifecycleAndReplayPrevention(): void
    {
        $pilot = User::findByUsername('VortexBlade');
        $this->assertNotNull($pilot, 'Seeded pilot VortexBlade should exist.');

        // 1. Start match handshake
        $handshake = MatchEngine::startMatch($pilot, [
            'vehicle' => 'striker',
            'arena' => 'neon_core',
            'difficulty' => 'normal',
        ]);

        $this->assertArrayHasKey('run_token', $handshake);
        $this->assertArrayHasKey('start_nonce', $handshake);

        $runToken = $handshake['run_token'];
        $nonce = $handshake['start_nonce'];

        // Synchronize server token issue time with test duration (45s) to test valid match
        \App\Core\Database::update('run_tokens', [
            'created_at' => gmdate('Y-m-d H:i:s', time() - 45),
        ], 'token_hash = :hash', [':hash' => hash('sha256', $runToken)]);

        // 2. Legitimate match finish submission
        $payload = [
            'run_token' => $runToken,
            'start_nonce' => $nonce,
            'score' => 8500,
            'waves' => 3,
            'kills' => 8,
            'duration' => 45,
            'accuracy' => 82.5,
            'combo_max' => 3,
            'telemetry' => [
                ['time' => 15, 'score' => 2000, 'hull' => 80],
                ['time' => 30, 'score' => 5000, 'hull' => 70],
                ['time' => 45, 'score' => 8500, 'hull' => 60],
            ],
        ];

        $initialXp = $pilot->xp;
        $result = MatchEngine::finishMatch($pilot, $payload, '127.0.0.1');

        $this->assertEquals('completed', $result['status']);
        $this->assertTrue($result['xp_gained'] > 0);

        // Pilot should have gained XP
        $reloaded = User::find($pilot->id);
        $this->assertEquals($initialXp + $result['xp_gained'], $reloaded->xp);

        // 3. Attempting to replay with the same run token MUST be rejected
        $replayRejected = false;
        try {
            MatchEngine::finishMatch($pilot, $payload, '127.0.0.1');
        } catch (\Exception $e) {
            $replayRejected = true;
            $this->assertStringContains('redeemed', $e->getMessage());
        }

        $this->assertTrue($replayRejected, 'Replaying a previously consumed run token must be strictly rejected.');
    }
}
