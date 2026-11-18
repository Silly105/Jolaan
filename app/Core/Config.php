<?php
declare(strict_types=1);

namespace App\Core;

class Config
{
    private static array $config = [];
    private static bool $loaded = false;

    public static function configPath(): string
    {
        return dirname(__DIR__, 2) . '/storage/config/config.php';
    }

    public static function keyPath(): string
    {
        return dirname(__DIR__, 2) . '/storage/config/secret.key';
    }

    public static function isInstalled(): bool
    {
        return is_file(self::configPath()) && is_file(self::keyPath());
    }

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        $path = self::configPath();
        if (is_file($path)) {
            $data = require $path;
            if (is_array($data)) {
                self::$config = $data;
            }
        }
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        return self::$config[$key] ?? $default;
    }

    public static function all(): array
    {
        self::load();
        return self::$config;
    }

    public static function save(array $newConfig): bool
    {
        self::load();
        self::$config = array_merge(self::$config, $newConfig);
        $export = var_export(self::$config, true);
        $content = "<?php\ndeclare(strict_types=1);\n\n// Automatisch generierte Konfigurationsdatei\nreturn " . $export . ";\n";
        $dir = dirname(self::configPath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return file_put_contents(self::configPath(), $content, LOCK_EX) !== false;
    }
}
