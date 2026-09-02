<?php

declare(strict_types=1);

// VOIDSTRIKE ARENA — Automated Test Runner
// Pure PHP 8.5 Test Execution Harness

$basePath = dirname(__DIR__);
require_once $basePath . '/vendor/autoload.php';

use App\Core\Config;
use App\Core\Env;
use Tests\TestCase;

// Bootstrap Environment for testing
Env::load($basePath . '/.env');
Config::init($basePath . '/config');
\App\Localization\Translator::init($basePath . '/resources/lang');
\App\Core\View::init($basePath . '/resources/views');
require_once $basePath . '/tests/TestCase.php';

$startTime = microtime(true);
$unitFiles = glob($basePath . '/tests/Unit/*Test.php') ?: [];
$integrationFiles = glob($basePath . '/tests/Integration/*Test.php') ?: [];
$testFiles = array_merge($unitFiles, $integrationFiles);

$totalTests = 0;
$totalAssertions = 0;
$failures = [];

echo "\n=======================================================\n";
echo "  VOIDSTRIKE ARENA — AUTOMATED TEST HARNESS\n";
echo "=======================================================\n\n";

foreach ($testFiles as $file) {
    require_once $file;

    $rel = str_replace([$basePath . '/tests/', $basePath . '\\tests\\', '.php'], '', $file);
    $className = 'Tests\\' . str_replace(['/', '\\'], '\\', $rel);

    if (!class_exists($className)) {
        continue;
    }

    $ref = new ReflectionClass($className);
    if ($ref->isAbstract()) {
        continue;
    }

    echo "Running " . $ref->getShortName() . ":\n";

    $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
    foreach ($methods as $method) {
        if (!str_starts_with($method->getName(), 'test')) {
            continue;
        }

        $totalTests++;
        /** @var TestCase $instance */
        $instance = new $className();

        try {
            $instance->runTest($method->getName());
            $totalAssertions += $instance->getAssertionsCount();
            echo "  ✔ " . $method->getName() . "\n";
        } catch (Throwable $e) {
            $failures[] = [
                'class' => $className,
                'method' => $method->getName(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
            echo "  ✖ " . $method->getName() . " (FAILED)\n";
        }
    }
    echo "\n";
}

$duration = round((microtime(true) - $startTime) * 1000, 2);

echo "=======================================================\n";
echo "TEST RESULTS:\n";
echo "  Tests run:    {$totalTests}\n";
echo "  Assertions:   {$totalAssertions}\n";
echo "  Time taken:   {$duration} ms\n";

if (empty($failures)) {
    echo "  Status:       ALL TESTS PASSED (100% SUCCESS)\n";
    echo "=======================================================\n\n";
    exit(0);
} else {
    echo "  Status:       " . count($failures) . " FAILURE(S) DETECTED\n";
    echo "=======================================================\n\n";

    foreach ($failures as $f) {
        echo "[FAILURE] {$f['class']}::{$f['method']}\n";
        echo "  Message: {$f['message']}\n";
        echo "  Location: {$f['file']}:{$f['line']}\n\n";
    }
    exit(1);
}
