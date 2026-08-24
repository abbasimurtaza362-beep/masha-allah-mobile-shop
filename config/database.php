<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/local_bootstrap.php';
require_once __DIR__ . '/../includes/catalog.php';


function db_config(string $key, string $fallback = '', ?string $railwayKey = null): string
{
    // First check normal variable
    $value = getenv($key);

    if (is_string($value) && $value !== '') {
        return $value;
    }

    // Then check Railway MySQL variable
    if ($railwayKey !== null) {
        $value = getenv($railwayKey);

        if (is_string($value) && $value !== '') {
            return $value;
        }
    }

    return $fallback;
}


function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }


    /*
     |--------------------------------------------------------------------------
     | Database Settings
     |--------------------------------------------------------------------------
     | Local XAMPP:
     | DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT
     |
     | Railway:
     | MYSQLHOST, MYSQLDATABASE, MYSQLUSER,
     | MYSQLPASSWORD, MYSQLPORT
     |--------------------------------------------------------------------------
     */

    $host = db_config(
        'DB_HOST',
        '127.0.0.1',
        'MYSQLHOST'
    );

    $name = db_config(
        'DB_NAME',
        'masha_allah_shop',
        'MYSQLDATABASE'
    );

    $user = db_config(
        'DB_USER',
        'root',
        'MYSQLUSER'
    );

    $pass = db_config(
        'DB_PASS',
        '',
        'MYSQLPASSWORD'
    );

    $port = db_config(
        'DB_PORT',
        '3306',
        'MYSQLPORT'
    );


    // Clean values
    $host = trim($host);
    $name = trim($name);
    $user = trim($user);
    $pass = (string)$pass;
    $port = trim($port);


    if (
        $host === '' ||
        $name === '' ||
        $user === '' ||
        $port === ''
    ) {
        throw new RuntimeException(
            'Database configuration is invalid.'
        );
    }


    if (
        !ctype_digit($port) ||
        (int)$port < 1 ||
        (int)$port > 65535
    ) {
        throw new RuntimeException(
            'Database port configuration is invalid.'
        );
    }


    $dsn =
        'mysql:host=' .
        $host .
        ';port=' .
        $port .
        ';dbname=' .
        $name .
        ';charset=utf8mb4';


    try {

        $pdo = local_db_pdo(
            $dsn,
            $user,
            $pass
        );


        if (local_bootstrap_allowed()) {
            local_apply_schema($pdo);
        }


    } catch (Throwable $error) {


        if (!local_bootstrap_allowed()) {
            throw $error;
        }


        try {

            $pdo = local_bootstrap_database(
                $host,
                $name,
                $user,
                $pass,
                $port
            );


        } catch (Throwable $bootstrapError) {


            throw new RuntimeException(
                'Database connection failed. Check Railway MySQL variables.'
            );

        }

    }


    auto_seed_catalog($pdo);


    return $pdo;
}
