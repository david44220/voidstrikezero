<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\Env;

// Bootstrap
Env::load(__DIR__ . '/../.env');
Config::init(__DIR__ . '/../config');

echo "===========================================\n";
echo " VOIDSTRIKE ARENA - Database Migration Tool\n";
echo "===========================================\n";

try {
    $pdo = Database::connection();
    echo "[OK] Connected to PostgreSQL (" . config('database.connections.pgsql.database') . ")\n";

    // Create migrations log table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id SERIAL PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $executed = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

    $migrationFiles = glob(__DIR__ . '/migrations/*.sql');
    sort($migrationFiles);

    $ranCount = 0;
    foreach ($migrationFiles as $file) {
        $filename = basename($file);
        if (in_array($filename, $executed, true)) {
            continue;
        }

        echo "Migrating: {$filename}... ";
        $sql = file_get_contents($file);

        $pdo->beginTransaction();
        try {
            $pdo->exec($sql);
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (:m)");
            $stmt->execute([':m' => $filename]);
            $pdo->commit();
            echo "[DONE]\n";
            $ranCount++;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            echo "[FAILED]\n";
            echo "Error in {$filename}: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    if ($ranCount === 0) {
        echo "Nothing to migrate. All tables are up to date.\n";
    } else {
        echo "Successfully executed {$ranCount} migration(s).\n";
    }

} catch (\Throwable $e) {
    echo "[FATAL ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
