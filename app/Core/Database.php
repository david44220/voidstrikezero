<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $default = (string) config('database.default', 'pgsql');
        $conf = config("database.connections.{$default}");

        if (!$conf || !is_array($conf)) {
            throw new RuntimeException("Database configuration for [{$default}] not found.");
        }

        $driver = $conf['driver'] ?? 'pgsql';
        $host = $conf['host'] ?? '127.0.0.1';
        $port = $conf['port'] ?? 5432;
        $dbname = $conf['database'] ?? 'voidstrike';
        $user = $conf['username'] ?? 'postgres';
        $pass = $conf['password'] ?? 'postgres';
        $sslmode = $conf['sslmode'] ?? 'prefer';

        $dsn = "{$driver}:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
        $options = $conf['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ];

        try {
            self::$instance = new PDO($dsn, $user, $pass, $options);
            if ($driver === 'pgsql') {
                self::$instance->exec("SET TIME ZONE 'UTC'");
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection error: " . $e->getMessage(), (int) $e->getCode(), $e);
        }

        return self::$instance;
    }

    public static function setConnection(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function select(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function selectOne(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public static function selectValue(string $sql, array $params = []): mixed
    {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function insert(string $table, array $data, ?string $returning = null): mixed
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        if ($returning !== null) {
            $sql .= " RETURNING {$returning}";
            $stmt = self::connection()->prepare($sql);
            $stmt->execute($data);
            return $stmt->fetchColumn();
        }

        $stmt = self::connection()->prepare($sql);
        $stmt->execute($data);
        return self::connection()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setClauses = [];
        $params = [];

        foreach ($data as $col => $val) {
            $setClauses[] = "{$col} = :set_{$col}";
            $params["set_{$col}"] = $val;
        }

        foreach ($whereParams as $k => $v) {
            $params[$k] = $v;
        }

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $table, implode(', ', $setClauses), $where);
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        if ($pdo->inTransaction()) {
            return $callback($pdo);
        }

        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
