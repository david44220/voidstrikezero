<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Achievements\AchievementService;
use App\Auth\AuthService;
use App\Challenges\ChallengeRepository;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Game\AntiCheatValidator;
use App\Game\MatchEngine;
use App\Matches\MatchRepository;
use App\Security\Csrf;
use App\Security\CsrfMiddleware;
use App\Security\SecurityHeadersMiddleware;
use App\Users\User;
use Tests\TestCase;

class RunIntegrityAndSecurityTest extends TestCase
{
    private User $pilotA;
    private User $pilotB;

    protected function setUp(): void
    {
        // Seed test pilots
        $this->pilotA = $this->getOrCreateUser('test_pilot_a', 'pilot_a@test.io');
        $this->pilotB = $this->getOrCreateUser('test_pilot_b', 'pilot_b@test.io');

        // Reset passwords for clean repeatable state
        $this->pilotA->password_hash = AuthService::hashPassword('SecretPass123!');
        $this->pilotA->save();
        $this->pilotB->password_hash = AuthService::hashPassword('SecretPass123!');
        $this->pilotB->save();
    }

    private function getOrCreateUser(string $username, string $email): User
    {
        $existing = User::findByUsername($username);
        if ($existing) {
            return $existing;
        }

        $user = new User([
            'username' => $username,
            'email' => $email,
            'password_hash' => AuthService::hashPassword('SecretPass123!'),
            'display_name' => ucfirst($username),
            'role' => 'player',
            'status' => 'active',
            'email_verified_at' => null,
            'xp' => 100,
            'level' => 1,
        ]);
        $user->save();
        return $user;
    }

    public function testGuestRunCannotAwardAuthenticatedXp(): void
    {
        // 1. Start match as guest (null user)
        $handshake = MatchEngine::startMatch(null, ['vehicle' => 'striker']);
        $rawToken = $handshake['run_token'];
        $nonce = $handshake['start_nonce'];

        // Synchronize creation time to avoid drift
        Database::update('run_tokens', [
            'created_at' => gmdate('Y-m-d H:i:s', time() - 35),
        ], 'token_hash = :hash', [':hash' => hash('sha256', $rawToken)]);

        // 2. Submit finish payload attempting to link to Pilot A
        $initialXp = $this->pilotA->xp;
        $payload = [
            'run_token' => $rawToken,
            'start_nonce' => $nonce,
            'score' => 6500,
            'waves' => 2,
            'kills' => 6,
            'duration' => 35,
            'accuracy' => 80.0,
            'combo_max' => 2,
            'shots_fired' => 20,
            'shots_hit' => 16,
            'damage_dealt' => 450,
            'damage_taken' => 30,
            'telemetry' => [
                ['time' => 10, 'score' => 2000, 'hull' => 80],
                ['time' => 20, 'score' => 4000, 'hull' => 70],
                ['time' => 35, 'score' => 6500, 'hull' => 50],
            ],
        ];

        $result = MatchEngine::finishMatch($this->pilotA, $payload, '127.0.0.1');

        // Must remain a guest run: no XP awarded
        $this->assertEquals(0, $result['xp_gained'], 'Guest runs must never award authenticated XP');
        $this->pilotA = User::find($this->pilotA->id);
        $this->assertEquals($initialXp, $this->pilotA->xp, 'Pilot XP must remain unchanged on guest runs');

        // Check match record in DB has user_id = null
        $match = Database::selectOne("SELECT user_id FROM matches WHERE id = :id", [':id' => $result['match_id']]);
        $this->assertNull($match['user_id'], 'Match record for guest run must have null user_id');
    }

    public function testAuthenticatedRunUserBindingMismatchRejected(): void
    {
        // Start run bound to Pilot A
        $handshake = MatchEngine::startMatch($this->pilotA, ['vehicle' => 'striker']);
        $rawToken = $handshake['run_token'];
        $nonce = $handshake['start_nonce'];

        Database::update('run_tokens', [
            'created_at' => gmdate('Y-m-d H:i:s', time() - 30),
        ], 'token_hash = :hash', [':hash' => hash('sha256', $rawToken)]);

        $payload = [
            'run_token' => $rawToken,
            'start_nonce' => $nonce,
            'score' => 5000,
            'waves' => 2,
            'kills' => 5,
            'duration' => 30,
            'telemetry' => [
                ['time' => 10, 'score' => 1500, 'hull' => 80],
                ['time' => 20, 'score' => 3000, 'hull' => 80],
                ['time' => 30, 'score' => 5000, 'hull' => 80],
            ],
        ];

        // Pilot B attempts to redeem Pilot A's run token
        $rejected = false;
        try {
            MatchEngine::finishMatch($this->pilotB, $payload, '127.0.0.1');
        } catch (\Exception $e) {
            $rejected = true;
            $this->assertStringContains('binding mismatch', $e->getMessage());
        }

        $this->assertTrue($rejected, 'Redeeming another user token must be strictly rejected');
    }

    public function testAtomicTokenRedemptionRejectsReplay(): void
    {
        $handshake = MatchEngine::startMatch($this->pilotA, ['vehicle' => 'striker']);
        $rawToken = $handshake['run_token'];
        $nonce = $handshake['start_nonce'];

        Database::update('run_tokens', [
            'created_at' => gmdate('Y-m-d H:i:s', time() - 30),
        ], 'token_hash = :hash', [':hash' => hash('sha256', $rawToken)]);

        $payload = [
            'run_token' => $rawToken,
            'start_nonce' => $nonce,
            'score' => 5000,
            'waves' => 2,
            'kills' => 5,
            'duration' => 30,
            'shots_fired' => 20,
            'shots_hit' => 15,
            'telemetry' => [
                ['time' => 10, 'score' => 1500, 'hull' => 80],
                ['time' => 20, 'score' => 3000, 'hull' => 80],
                ['time' => 30, 'score' => 5000, 'hull' => 80],
            ],
        ];

        // 1st redemption succeeds
        $result = MatchEngine::finishMatch($this->pilotA, $payload, '127.0.0.1');
        $this->assertEquals('completed', $result['status']);

        // 2nd redemption strictly rejected atomically
        $replayBlocked = false;
        try {
            MatchEngine::finishMatch($this->pilotA, $payload, '127.0.0.1');
        } catch (\Exception $e) {
            $replayBlocked = true;
            $this->assertStringContains('already redeemed', $e->getMessage());
        }

        $this->assertTrue($replayBlocked, 'Replaying consumed token must fail atomically');
    }

    public function testStartNonceValidationEnforced(): void
    {
        $handshake = MatchEngine::startMatch($this->pilotA, ['vehicle' => 'striker']);
        $rawToken = $handshake['run_token'];

        Database::update('run_tokens', [
            'created_at' => gmdate('Y-m-d H:i:s', time() - 30),
        ], 'token_hash = :hash', [':hash' => hash('sha256', $rawToken)]);

        $payload = [
            'run_token' => $rawToken,
            'start_nonce' => 'corrupted_forged_nonce',
            'score' => 5000,
            'duration' => 30,
        ];

        $nonceFailed = false;
        try {
            MatchEngine::finishMatch($this->pilotA, $payload, '127.0.0.1');
        } catch (\Exception $e) {
            $nonceFailed = true;
            $this->assertStringContains('nonce', $e->getMessage());
        }

        $this->assertTrue($nonceFailed, 'Manipulated start nonce must be rejected');
    }

    public function testChallengeConfigurationEnforcedAndServerAuthoritative(): void
    {
        // Create an active challenge: requires titan, magma_foundry, hard
        $chalId = ChallengeRepository::create($this->pilotA->id, 25000, 'titan', 'magma_foundry', 'hard', 7);

        // Client attempts to start challenge with striker on easy
        $handshake = MatchEngine::startMatch($this->pilotB, [
            'mode' => 'challenge',
            'challenge_id' => $chalId,
            'vehicle' => 'striker',
            'arena' => 'neon_core',
            'difficulty' => 'easy',
        ]);

        // Server MUST override and force challenge settings
        $this->assertEquals('titan', $handshake['vehicle'], 'Challenge vehicle must be enforced by server');
        $this->assertEquals('magma_foundry', $handshake['arena'], 'Challenge arena must be enforced by server');
        $this->assertEquals('hard', $handshake['difficulty'], 'Challenge difficulty must be enforced by server');
    }

    public function testLeaderboardPlayerDeduplication(): void
    {
        // Insert multiple matches for pilot A: 50,000 and 30,000
        $matchId1 = 'm_dedup_' . bin2hex(random_bytes(8));
        $matchId2 = 'm_dedup_' . bin2hex(random_bytes(8));

        Database::insert('matches', [
            'id' => $matchId1,
            'user_id' => $this->pilotA->id,
            'vehicle_class' => 'striker',
            'arena_id' => 'neon_core',
            'difficulty' => 'normal',
            'score' => 50000,
            'status' => 'completed',
            'duration_seconds' => 120,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'finished_at' => gmdate('Y-m-d H:i:s'),
        ]);

        Database::insert('matches', [
            'id' => $matchId2,
            'user_id' => $this->pilotA->id,
            'vehicle_class' => 'striker',
            'arena_id' => 'neon_core',
            'difficulty' => 'normal',
            'score' => 30000,
            'status' => 'completed',
            'duration_seconds' => 80,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'finished_at' => gmdate('Y-m-d H:i:s'),
        ]);

        $leaderboard = MatchRepository::getGlobalLeaderboard(100);

        // Count occurrences of pilot A
        $pilotACount = 0;
        $highestScore = 0;
        foreach ($leaderboard as $row) {
            if ((int) $row['user_id'] === $this->pilotA->id) {
                $pilotACount++;
                $highestScore = (int) $row['score'];
            }
        }

        $this->assertEquals(1, $pilotACount, 'A player must only occupy ONE ranking position on leaderboard');
        $this->assertTrue($highestScore >= 50000, 'Leaderboard must display personal best score');
    }

    public function testFabricatedAchievementMetricsBlocked(): void
    {
        // Client claims 2000 absorbed damage and 15 phase shifts while flying Striker
        $unlocked = AchievementService::checkAndAward($this->pilotA, [
            'vehicle' => 'striker',
            'absorbed_damage' => 2000,
            'phase_shifts' => 15,
            'specials_used' => 0,
            'kills' => 0,
            'waves' => 1,
            'damage_taken' => 50,
            'duration' => 30,
        ]);

        $unlockedCodes = array_column($unlocked, 'code');
        $this->assertFalse(in_array('titan_wall', $unlockedCodes, true), 'Titan wall cannot be unlocked by Striker');
        $this->assertFalse(in_array('phase_walker', $unlockedCodes, true), 'Phase walker cannot be unlocked by Striker');
        $this->assertFalse(in_array('first_blood', $unlockedCodes, true), 'First blood cannot be unlocked with 0 kills');
    }

    public function testEmailVerificationWorkflow(): void
    {
        $rawVerifyToken = bin2hex(random_bytes(32));
        $hash = hash('sha256', $rawVerifyToken);

        Database::insert('email_verifications', [
            'user_id' => $this->pilotB->id,
            'token_hash' => $hash,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        $req = Request::create('GET', '/verify-email', [
            'token' => $rawVerifyToken,
            'email' => $this->pilotB->email,
        ]);

        $router = new Router();
        $authController = new \App\Auth\AuthController();
        $router->get('/verify-email', [$authController, 'verifyEmail']);

        $res = $router->dispatch($req);
        $this->assertEquals(302, $res->getStatusCode());

        $reloaded = User::find($this->pilotB->id);
        $this->assertTrue($reloaded->isEmailVerified(), 'Pilot email must be verified after valid token submission');
    }

    public function testCsrfProtectionRejectsForgedPost(): void
    {
        $router = new Router();
        $router->use(new CsrfMiddleware());
        $router->post('/test-action', function () {
            return new \App\Core\Response('protected');
        });

        // Request without CSRF token must be rejected with HTTP 419
        $req = Request::create('POST', '/test-action');
        $res = $router->dispatch($req);

        $this->assertEquals(419, $res->getStatusCode(), 'State-modifying POST without CSRF token must return 419');
    }

    public function testPasswordChangeWorkflowAndSessionInvalidation(): void
    {
        AuthService::login($this->pilotA);

        $userController = new \App\Users\UserController();

        // 1. Wrong current password fails
        $badReq = Request::create('POST', '/settings/password', [
            'current_password' => 'WrongPass999!',
            'new_password' => 'BrandNewPass2026!',
            'new_password_confirmation' => 'BrandNewPass2026!',
        ]);

        $res1 = $userController->changePassword($badReq);
        $this->assertEquals(302, $res1->getStatusCode());
        $this->assertEquals('/settings', $res1->getHeader('Location'));

        // Verify password did NOT change
        $userA = User::find($this->pilotA->id);
        $this->assertFalse($userA->verifyPassword('BrandNewPass2026!'));

        // 2. Correct current password succeeds
        $goodReq = Request::create('POST', '/settings/password', [
            'current_password' => 'SecretPass123!',
            'new_password' => 'BrandNewPass2026!',
            'new_password_confirmation' => 'BrandNewPass2026!',
        ]);

        $res2 = $userController->changePassword($goodReq);
        $this->assertEquals(302, $res2->getStatusCode());

        // Verify password DID change
        $userA = User::find($this->pilotA->id);
        $this->assertTrue($userA->verifyPassword('BrandNewPass2026!'), 'Password must be successfully updated in DB');
    }

    public function testPasswordResetConcealsTokenFromUi(): void
    {
        $authController = new \App\Auth\AuthController();

        $req = Request::create('POST', '/forgot-password', [
            'email' => $this->pilotA->email,
        ]);

        $res = $authController->sendResetLink($req);
        $this->assertEquals(302, $res->getStatusCode());

        // Ensure raw token is NOT present anywhere in session flash
        $flashSuccess = (string) Session::getInstance()->getFlash('success');
        $this->assertStringNotContains('token', strtolower($flashSuccess));

        // Verify record in password_resets table
        $resetRecord = Database::selectOne(
            "SELECT * FROM password_resets WHERE email = :email",
            [':email' => $this->pilotA->email]
        );
        $this->assertNotNull($resetRecord, 'Password reset hash must be stored in database');
        $this->assertEquals(64, strlen($resetRecord['token_hash']), 'Token hash must be SHA-256');
    }

    public function testAdminAuthorizationDeniedToRegularPlayers(): void
    {
        AuthService::login($this->pilotA); // pilotA is role 'player'

        $router = new Router();
        $router->group(['prefix' => '/admin', 'middleware' => [\App\Admin\AdminMiddleware::class]], function (Router $r) {
            $r->get('/settings', function () {
                return new \App\Core\Response('secret_admin_area');
            });
        });

        $req = Request::create('GET', '/admin/settings');
        $res = $router->dispatch($req);

        $this->assertEquals(403, $res->getStatusCode(), 'Non-admin users must receive HTTP 403 Forbidden');
    }

    public function testChallengeAttemptOnlyUpdatesWhenScoreExceeded(): void
    {
        $chalId = ChallengeRepository::create($this->pilotA->id, 10000, 'striker', 'neon_core', 'normal', 7);

        $mId1 = 'm_att_' . bin2hex(random_bytes(8));
        $mId2 = 'm_att_' . bin2hex(random_bytes(8));
        $mId3 = 'm_att_' . bin2hex(random_bytes(8));

        // Pre-insert matching finished run records to satisfy FK
        foreach ([$mId1 => 15000, $mId2 => 12000, $mId3 => 22000] as $mid => $sc) {
            Database::insert('matches', [
                'id' => $mid,
                'user_id' => $this->pilotB->id,
                'vehicle_class' => 'striker',
                'arena_id' => 'neon_core',
                'difficulty' => 'normal',
                'score' => $sc,
                'status' => 'completed',
                'duration_seconds' => 50,
                'created_at' => gmdate('Y-m-d H:i:s'),
                'finished_at' => gmdate('Y-m-d H:i:s'),
            ]);
        }

        // Attempt 1: Score 15,000 (beats target)
        $attId1 = ChallengeRepository::recordAttempt($chalId, $this->pilotB->id, $mId1, 15000);
        $chal = ChallengeRepository::find($chalId);
        $this->assertEquals($attId1, $chal['best_attempt_id']);

        // Attempt 2: Score 12,000 (lower than Attempt 1)
        $attId2 = ChallengeRepository::recordAttempt($chalId, $this->pilotB->id, $mId2, 12000);
        $chal = ChallengeRepository::find($chalId);
        $this->assertEquals($attId1, $chal['best_attempt_id'], 'Lower score attempt must not overwrite best attempt');

        // Attempt 3: Score 22,000 (higher than Attempt 1)
        $attId3 = ChallengeRepository::recordAttempt($chalId, $this->pilotB->id, $mId3, 22000);
        $chal = ChallengeRepository::find($chalId);
        $this->assertEquals($attId3, $chal['best_attempt_id'], 'Higher score attempt must update best attempt');
    }
}

