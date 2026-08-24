<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/local_bootstrap.php';
require_once __DIR__ . '/../includes/catalog.php';

function db_config(string $key, string $fallback): string
{
    $value = getenv($key);
    return is_string($value) && $value !== '' ? $value : $fallback;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = db_config('DB_HOST', '127.0.0.1');
    $name = db_config('DB_NAME', 'masha_allah_shop');
    $user = db_config('DB_USER', 'root');
    $pass = db_config('DB_PASS', '');
    $port = db_config('DB_PORT', '3306');

    if (!preg_match('/^[A-Za-z0-9._-]+$/', $host) || !preg_match('/^[A-Za-z0-9_]+$/', $name) || !preg_match('/^\d{1,5}$/', $port)) {
        throw new RuntimeException('Database configuration is invalid.');
    }

    $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
    try {
        $pdo = local_db_pdo($dsn, $user, $pass);
        if (local_bootstrap_allowed()) local_apply_schema($pdo);
    } catch (Throwable $error) {
        if (!local_bootstrap_allowed()) throw $error;
        try {
            $pdo = local_bootstrap_database($host, $name, $user, $pass, $port);
        } catch (Throwable $bootstrapError) {
            throw new RuntimeException('Local database setup could not run. Start MySQL in XAMPP and confirm the root database account can create masha_allah_shop.');
        }
    }

    auto_seed_catalog($pdo);
    return $pdo;
}
