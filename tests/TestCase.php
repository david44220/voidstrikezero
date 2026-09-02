<?php

declare(strict_types=1);

namespace Tests;

use Exception;

abstract class TestCase
{
    private int $assertions = 0;

    public function getAssertionsCount(): int
    {
        return $this->assertions;
    }

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    public function runTest(string $method): void
    {
        $this->setUp();
        try {
            $this->$method();
        } finally {
            $this->tearDown();
        }
    }

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        $this->assertions++;
        if (!$condition) {
            throw new Exception($message ?: 'Failed asserting that condition is true.');
        }
    }

    protected function assertFalse(bool $condition, string $message = ''): void
    {
        $this->assertions++;
        if ($condition) {
            throw new Exception($message ?: 'Failed asserting that condition is false.');
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            $expStr = var_export($expected, true);
            $actStr = var_export($actual, true);
            throw new Exception($message ?: "Failed asserting that {$actStr} matches expected {$expStr}.");
        }
    }

    protected function assertNull(mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($actual !== null) {
            throw new Exception($message ?: 'Failed asserting that value is null.');
        }
    }

    protected function assertNotNull(mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($actual === null) {
            throw new Exception($message ?: 'Failed asserting that value is not null.');
        }
    }

    protected function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new Exception($message ?: "Failed asserting that '{$haystack}' contains '{$needle}'.");
        }
    }

    protected function assertStringNotContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (str_contains($haystack, $needle)) {
            throw new Exception($message ?: "Failed asserting that '{$haystack}' does not contain '{$needle}'.");
        }
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        $this->assertions++;
        if (!array_key_exists($key, $array)) {
            throw new Exception($message ?: "Failed asserting that array contains key '{$key}'.");
        }
    }

    protected function assertGreaterThanOrEqual(int|float $expected, int|float $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($actual < $expected) {
            throw new Exception($message ?: "Failed asserting that {$actual} is greater than or equal to {$expected}.");
        }
    }
}
