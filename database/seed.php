<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth\AuthService;
use App\Core\Config;
use App\Core\Database;
use App\Core\Env;

// Bootstrap
Env::load(__DIR__ . '/../.env');
Config::init(__DIR__ . '/../config');

echo "=======================================\n";
echo " VOIDSTRIKE ARENA - Database Seeder\n";
echo "=======================================\n";

$isProduction = config('app.env') === 'production';
$force = in_array('--force', $argv ?? [], true);

if ($isProduction && !$force) {
    echo "[ABORTED] Cannot execute database demo seeder in production without explicit --force flag.\n";
    exit(1);
}

$adminPassword = $isProduction ? bin2hex(random_bytes(16)) : 'AdminPassword2026!';
if ($isProduction) {
    echo "\n[CRITICAL SECURITY ALERT] Production administrator account initialized.\n";
    echo "Temporary Master Password: {$adminPassword}\n";
    echo "Store this securely and rotate immediately.\n\n";
}

try {
    $pdo = Database::connection();
    echo "[OK] Connected to database.\n";

    // 1. Seed Achievements
    echo "Seeding achievements... ";
    $achievements = [
        [
            'code' => 'first_blood',
            'name_en' => 'First Blood',
            'name_fr' => 'Premier Sang',
            'description_en' => 'Complete your first arena combat mission.',
            'description_fr' => 'Terminez votre première mission de combat en arène.',
            'icon' => 'target',
            'category' => 'combat',
            'xp_reward' => 150,
        ],
        [
            'code' => 'apex_survivor',
            'name_en' => 'Apex Survivor',
            'name_fr' => 'Survivant Alpha',
            'description_en' => 'Survive and clear 5 consecutive hostile waves.',
            'description_fr' => 'Survivez et éliminez 5 vagues hostiles consécutives.',
            'icon' => 'shield',
            'category' => 'survival',
            'xp_reward' => 300,
        ],
        [
            'code' => 'untouchable',
            'name_en' => 'Untouchable',
            'name_fr' => 'Intouchable',
            'description_en' => 'Complete a combat match taking less than 20 hull damage.',
            'description_fr' => 'Terminez un match en subissant moins de 20 dégâts de coque.',
            'icon' => 'zap',
            'category' => 'skill',
            'xp_reward' => 500,
        ],
        [
            'code' => 'combo_master',
            'name_en' => 'Combo Sovereign',
            'name_fr' => 'Maître du Combo',
            'description_en' => 'Achieve and maintain a maximum 5.0x combo score multiplier.',
            'description_fr' => 'Atteignez et maintenez un multiplicateur de score combo maximal de 5.0x.',
            'icon' => 'flame',
            'category' => 'combat',
            'xp_reward' => 350,
        ],
        [
            'code' => 'titan_wall',
            'name_en' => 'Titan Bastion',
            'name_fr' => 'Bastion du Titan',
            'description_en' => 'Absorb over 600 damage using Titan kinetic shielding in a single run.',
            'description_fr' => 'Absorbez plus de 600 dégâts avec le blindage cinétique du Titan.',
            'icon' => 'shield-alert',
            'category' => 'vehicle',
            'xp_reward' => 400,
        ],
        [
            'code' => 'speed_demon',
            'name_en' => 'Speed Demon',
            'name_fr' => 'Démon de la Vitesse',
            'description_en' => 'Eliminate 15 hostile drones in under 60 seconds with Striker.',
            'description_fr' => 'Éliminez 15 drones hostiles en moins de 60 secondes avec le Striker.',
            'icon' => 'wind',
            'category' => 'vehicle',
            'xp_reward' => 450,
        ],
        [
            'code' => 'phase_walker',
            'name_en' => 'Subspace Phantasm',
            'name_fr' => 'Spectre Subspatial',
            'description_en' => 'Successfully execute 12 Phase Shifts while damaging enemies with Phantom.',
            'description_fr' => 'Exécutez 12 Sauts de Phase en infligeant des dégâts avec le Phantom.',
            'icon' => 'activity',
            'category' => 'vehicle',
            'xp_reward' => 400,
        ],
        [
            'code' => 'challenger',
            'name_en' => 'Gauntlet Master',
            'name_fr' => 'Maître du Défi',
            'description_en' => 'Create a custom duel challenge or beat a rival player’s target score.',
            'description_fr' => 'Créez un duel personnalisé ou battez le score d’un rival.',
            'icon' => 'award',
            'category' => 'community',
            'xp_reward' => 250,
        ],
        [
            'code' => 'grandmaster',
            'name_en' => 'Void Grandmaster',
            'name_fr' => 'Grand Maître du Néant',
            'description_en' => 'Attain Pilot Clearance Level 10 on the galactic grid.',
            'description_fr' => 'Atteignez le Niveau d’Accréditation 10 sur la grille galactique.',
            'icon' => 'star',
            'category' => 'progression',
            'xp_reward' => 1000,
        ],
    ];

    foreach ($achievements as $ach) {
        $exists = Database::selectOne("SELECT id FROM achievements WHERE code = :c", [':c' => $ach['code']]);
        if (!$exists) {
            Database::insert('achievements', $ach);
        }
    }
    echo "[DONE]\n";

    // 2. Seed Admin & Test Users
    echo "Seeding pilot accounts... ";
    $users = [
        [
            'username' => 'admin',
            'email' => 'admin@voidstrike.io',
            'password' => $adminPassword,
            'display_name' => 'Nexus Commander',
            'role' => 'admin',
            'xp' => 45000,
            'level' => 22,
            'selected_vehicle' => 'striker',
            'preferred_locale' => 'en',
        ],
        [
            'username' => 'VortexBlade',
            'email' => 'vortex@voidstrike.io',
            'password' => 'PilotPass2026!',
            'display_name' => 'Vortex Blade',
            'role' => 'player',
            'xp' => 28900,
            'level' => 17,
            'selected_vehicle' => 'striker',
            'preferred_locale' => 'en',
        ],
        [
            'username' => 'ObsidianAegis',
            'email' => 'aegis@voidstrike.io',
            'password' => 'PilotPass2026!',
            'display_name' => 'Obsidian Aegis',
            'role' => 'player',
            'xp' => 19600,
            'level' => 14,
            'selected_vehicle' => 'titan',
            'preferred_locale' => 'fr',
        ],
        [
            'username' => 'GhostRider99',
            'email' => 'ghost@voidstrike.io',
            'password' => 'PilotPass2026!',
            'display_name' => 'Ghost Rider',
            'role' => 'player',
            'xp' => 12100,
            'level' => 11,
            'selected_vehicle' => 'phantom',
            'preferred_locale' => 'en',
        ],
        [
            'username' => 'CyberPulse',
            'email' => 'pulse@voidstrike.io',
            'password' => 'PilotPass2026!',
            'display_name' => 'Cyber Pulse',
            'role' => 'player',
            'xp' => 4900,
            'level' => 7,
            'selected_vehicle' => 'striker',
            'preferred_locale' => 'fr',
        ],
        [
            'username' => 'NeonViper',
            'email' => 'viper@voidstrike.io',
            'password' => 'PilotPass2026!',
            'display_name' => 'Neon Viper',
            'role' => 'player',
            'xp' => 1600,
            'level' => 4,
            'selected_vehicle' => 'phantom',
            'preferred_locale' => 'en',
        ],
    ];

    $userIds = [];
    foreach ($users as $u) {
        $row = Database::selectOne("SELECT id FROM users WHERE username = :u", [':u' => $u['username']]);
        if (!$row) {
            $id = Database::insert('users', [
                'username' => $u['username'],
                'email' => $u['email'],
                'password_hash' => AuthService::hashPassword($u['password']),
                'display_name' => $u['display_name'],
                'role' => $u['role'],
                'status' => 'active',
                'email_verified_at' => gmdate('Y-m-d H:i:s'),
                'xp' => $u['xp'],
                'level' => $u['level'],
                'selected_vehicle' => $u['selected_vehicle'],
                'preferred_locale' => $u['preferred_locale'],
                'created_at' => gmdate('Y-m-d H:i:s', strtotime('-14 days')),
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ], 'id');
            $userIds[$u['username']] = (int) $id;
        } else {
            $userIds[$u['username']] = (int) $row['id'];
        }
    }
    echo "[DONE]\n";

    // 3. Seed Validated Match Runs for Leaderboards
    echo "Seeding initial ranked matches... ";
    $sampleMatches = [
        [
            'id' => 'm_seed_001_' . bin2hex(random_bytes(8)),
            'user' => 'VortexBlade',
            'vehicle' => 'striker',
            'arena' => 'neon_core',
            'diff' => 'hard',
            'score' => 48250,
            'waves' => 12,
            'kills' => 54,
            'accuracy' => 84.5,
            'combo' => 5,
            'duration' => 195,
            'days_ago' => 1,
        ],
        [
            'id' => 'm_seed_002_' . bin2hex(random_bytes(8)),
            'user' => 'ObsidianAegis',
            'vehicle' => 'titan',
            'arena' => 'magma_foundry',
            'diff' => 'hard',
            'score' => 41800,
            'waves' => 11,
            'kills' => 48,
            'accuracy' => 78.2,
            'combo' => 4,
            'duration' => 210,
            'days_ago' => 2,
        ],
        [
            'id' => 'm_seed_003_' . bin2hex(random_bytes(8)),
            'user' => 'GhostRider99',
            'vehicle' => 'phantom',
            'arena' => 'orbital_station',
            'diff' => 'normal',
            'score' => 32400,
            'waves' => 9,
            'kills' => 38,
            'accuracy' => 89.0,
            'combo' => 5,
            'duration' => 165,
            'days_ago' => 3,
        ],
        [
            'id' => 'm_seed_004_' . bin2hex(random_bytes(8)),
            'user' => 'CyberPulse',
            'vehicle' => 'striker',
            'arena' => 'neon_core',
            'diff' => 'normal',
            'score' => 24150,
            'waves' => 7,
            'kills' => 28,
            'accuracy' => 74.0,
            'combo' => 3,
            'duration' => 140,
            'days_ago' => 4,
        ],
        [
            'id' => 'm_seed_005_' . bin2hex(random_bytes(8)),
            'user' => 'NeonViper',
            'vehicle' => 'phantom',
            'arena' => 'magma_foundry',
            'diff' => 'easy',
            'score' => 16200,
            'waves' => 5,
            'kills' => 20,
            'accuracy' => 71.5,
            'combo' => 3,
            'duration' => 125,
            'days_ago' => 5,
        ],
    ];

    $matchCount = (int) Database::selectValue("SELECT COUNT(*) FROM matches");
    if ($matchCount === 0) {
        foreach ($sampleMatches as $sm) {
            $uid = $userIds[$sm['user']] ?? null;
            $finishedAt = gmdate('Y-m-d H:i:s', strtotime("-{$sm['days_ago']} days"));
            Database::insert('matches', [
                'id' => $sm['id'],
                'user_id' => $uid,
                'vehicle_class' => $sm['vehicle'],
                'arena_id' => $sm['arena'],
                'difficulty' => $sm['diff'],
                'mode' => 'quick',
                'score' => $sm['score'],
                'waves_cleared' => $sm['waves'],
                'kills' => $sm['kills'],
                'accuracy' => $sm['accuracy'],
                'combo_max' => $sm['combo'],
                'duration_seconds' => $sm['duration'],
                'status' => 'completed',
                'anti_cheat_flags' => json_encode(['status' => 'clean', 'risk_score' => 0]),
                'created_at' => $finishedAt,
                'finished_at' => $finishedAt,
            ]);
        }
    }
    echo "[DONE]\n";

    // 4. Seed Sample Challenges
    echo "Seeding open challenges... ";
    $challengeCount = (int) Database::selectValue("SELECT COUNT(*) FROM challenges");
    if ($challengeCount === 0) {
        $c1Id = 'c_chal_001_' . bin2hex(random_bytes(6));
        Database::insert('challenges', [
            'id' => $c1Id,
            'creator_id' => $userIds['VortexBlade'],
            'target_score' => 35000,
            'vehicle_class' => 'striker',
            'arena_id' => 'neon_core',
            'difficulty' => 'hard',
            'expires_at' => gmdate('Y-m-d H:i:s', strtotime('+7 days')),
            'status' => 'active',
            'challenger_count' => 3,
            'created_at' => gmdate('Y-m-d H:i:s', strtotime('-1 day')),
        ]);

        $c2Id = 'c_chal_002_' . bin2hex(random_bytes(6));
        Database::insert('challenges', [
            'id' => $c2Id,
            'creator_id' => $userIds['ObsidianAegis'],
            'target_score' => 30000,
            'vehicle_class' => 'titan',
            'arena_id' => 'magma_foundry',
            'difficulty' => 'normal',
            'expires_at' => gmdate('Y-m-d H:i:s', strtotime('+10 days')),
            'status' => 'active',
            'challenger_count' => 1,
            'created_at' => gmdate('Y-m-d H:i:s', strtotime('-2 days')),
        ]);
    }
    echo "[DONE]\n";

    echo "\nSeeding completed successfully!\n";
    echo "=======================================\n";
    echo "Admin credentials:\n";
    echo "  Callsign: admin\n";
    echo "  Email:    admin@voidstrike.io\n";
    echo "  Passcode: AdminPassword2026!\n";
    echo "=======================================\n";

} catch (\Throwable $e) {
    echo "[FATAL ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
