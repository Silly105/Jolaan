<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?PDO $pdo = null;

    public static function connect(?array $overrideConfig = null): PDO
    {
        if (self::$pdo !== null && $overrideConfig === null) {
            return self::$pdo;
        }

        $host = $overrideConfig['db_host'] ?? Config::get('db_host', 'localhost');
        $port = (int)($overrideConfig['db_port'] ?? Config::get('db_port', 3306));
        $name = $overrideConfig['db_name'] ?? Config::get('db_name', '');
        $user = $overrideConfig['db_user'] ?? Config::get('db_user', '');
        $pass = $overrideConfig['db_pass'] ?? Config::get('db_pass', '');
        $prefix = $overrideConfig['db_prefix'] ?? Config::get('db_prefix', 'con_');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, $user, $pass, $options);
        if ($overrideConfig === null) {
            self::$pdo = $pdo;
        }
        return $pdo;
    }

    public static function table(string $name): string
    {
        $prefix = (string)Config::get('db_prefix', 'con_');
        // Nur alphanumerische Zeichen und Unterstrich erlauben
        $cleanPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix);
        $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        return '`' . $cleanPrefix . $cleanName . '`';
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchScalar(string $sql, array $params = []): mixed
    {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function lastInsertId(): int
    {
        return (int)self::connect()->lastInsertId();
    }

    public static function getSetting(string $key, mixed $default = null): mixed
    {
        try {
            $table = self::table('settings');
            $row = self::fetchOne("SELECT setting_value FROM {$table} WHERE setting_key = :k LIMIT 1", ['k' => $key]);
            return $row !== null ? $row['setting_value'] : $default;
        } catch (PDOException) {
            return $default;
        }
    }

    public static function setSetting(string $key, string $value): void
    {
        $table = self::table('settings');
        $sql = "INSERT INTO {$table} (setting_key, setting_value) VALUES (:k, :v)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        self::query($sql, ['k' => $key, 'v' => $value]);
    }
}
