<?php
declare(strict_types=1);

function local_request_from_machine(): bool
{
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return in_array($remote, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true);
}

function local_bootstrap_allowed(): bool
{
    return getenv('APP_LOCAL_BOOTSTRAP') !== '0' && local_request_from_machine();
}

function local_db_pdo(string $dsn, string $user, string $pass): PDO
{
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function local_apply_schema(PDO $pdo): void
{
    $schemaFile = __DIR__ . '/../schema.sql';
    $schema = is_file($schemaFile) ? file_get_contents($schemaFile) : false;
    if (!is_string($schema) || trim($schema) === '') {
        throw new RuntimeException('The local database schema file is missing.');
    }

    $statements = preg_split('/;\s*(?:\r\n|\n|\r|$)/', $schema) ?: [];
    foreach ($statements as $statement) {
        $statement = trim(preg_replace('/^\s*--.*$/m', '', $statement) ?? '');
        if ($statement === '' || preg_match('/^USE\s+/i', $statement)) continue;
        $pdo->exec($statement);
    }

    local_ensure_catalogue_columns($pdo);
    local_ensure_service_integrity($pdo);
    local_ensure_requested_admin($pdo);
}

function local_ensure_requested_admin(PDO $pdo): void
{
    if ($pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn() !== 'users') return;
    if ($pdo->query("SHOW TABLES LIKE 'app_settings'")->fetchColumn() !== 'app_settings') return;

    $syncMarker = $pdo->prepare(
        "SELECT setting_value FROM app_settings WHERE setting_key = 'requested_admin_sync' LIMIT 1"
    );
    $syncMarker->execute();
    // Use a versioned marker so existing installations receive this credential repair once.
    if ((string)$syncMarker->fetchColumn() === '2') return;

    // This is local-only bootstrap configuration for the shop owner account.
    // Store only the bcrypt hash here; never store the plain password.
    $email = 'test@gmail.com';
    $passwordHash = '$2y$10$UrRqH50ju4h7EJ9ZKZkbl.XJAQte3OleatXEmfLQozd9hrj2nIckC';
    $admin = $pdo->query(
        "SELECT id, email, password_hash
         FROM users
         WHERE role = 'admin'
         ORDER BY id
         LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    // The email column is unique. Release the requested address from a
    // non-admin local account before creating/updating the owner account.
    $conflict = $pdo->prepare(
        "SELECT id FROM users WHERE email = ? AND role <> 'admin' LIMIT 1"
    );
    $conflict->execute([$email]);
    $conflictId = $conflict->fetchColumn();
    if ($conflictId) {
        $releaseEmail = $pdo->prepare(
            "UPDATE users SET email = CONCAT('customer+', id, '@local.invalid') WHERE id = ?"
        );
        $releaseEmail->execute([(int)$conflictId]);
    }

    if (!$admin) {
        $insert = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role, status)
             VALUES ('Shop Administrator', ?, ?, 'admin', 'active')"
        );
        $insert->execute([$email, $passwordHash]);
    } elseif ((string)$admin['email'] !== $email || !hash_equals($passwordHash, (string)$admin['password_hash'])) {
        $update = $pdo->prepare(
            "UPDATE users
             SET email = ?, password_hash = ?, status = 'active', session_version = session_version + 1
             WHERE id = ? AND role = 'admin'"
        );
        try {
            $update->execute([$email, $passwordHash, (int)$admin['id']]);
        } catch (Throwable $error) {
            error_log('Requested local admin account could not be synchronized: ' . $error->getMessage());
            return;
        }
    }

    $markSync = $pdo->prepare(
        "INSERT INTO app_settings (setting_key, setting_value)
         VALUES ('requested_admin_sync', '2')
         ON DUPLICATE KEY UPDATE setting_value = '2'"
    );
    $markSync->execute();
}

function local_ensure_service_integrity(PDO $pdo): void
{
    if ($pdo->query("SHOW TABLES LIKE 'services'")->fetchColumn() !== 'services') return;

    // Keep the oldest row for each exact service name before adding the
    // unique index. This cleans duplicates created by older schema runs.
    $duplicates = $pdo->query(
        "SELECT name, MIN(id) AS keep_id
         FROM services
         GROUP BY name
         HAVING COUNT(*) > 1"
    )->fetchAll(PDO::FETCH_ASSOC);

    if ($duplicates) {
        $delete = $pdo->prepare('DELETE FROM services WHERE name = ? AND id <> ?');
        foreach ($duplicates as $duplicate) {
            $delete->execute([(string)$duplicate['name'], (int)$duplicate['keep_id']]);
        }
    }

    $hasUniqueName = $pdo->query(
        "SELECT COUNT(*)
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'services'
           AND INDEX_NAME = 'uq_services_name'"
    )->fetchColumn();

    if (!(int)$hasUniqueName) {
        try {
            $pdo->exec('ALTER TABLE services ADD UNIQUE KEY uq_services_name (name)');
        } catch (Throwable $error) {
            error_log('Service uniqueness migration failed: ' . $error->getMessage());
        }
    }
}

function local_has_column(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $statement->execute([$table, $column]);
    return (int)$statement->fetchColumn() > 0;
}

function local_ensure_catalogue_columns(PDO $pdo): void
{
    if ($pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn() === 'users' && !local_has_column($pdo, 'users', 'status')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active','blocked') NOT NULL DEFAULT 'active' AFTER role");
    }

    if ($pdo->query("SHOW TABLES LIKE 'categories'")->fetchColumn() === 'categories' && !local_has_column($pdo, 'categories', 'status')) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER name");
    }

    if ($pdo->query("SHOW TABLES LIKE 'products'")->fetchColumn() !== 'products') return;

    if (!local_has_column($pdo, 'products', 'reorder_level')) {
        $pdo->exec('ALTER TABLE products ADD COLUMN reorder_level INT NOT NULL DEFAULT 5 AFTER quantity');
    }
    if (!local_has_column($pdo, 'products', 'status')) {
        $pdo->exec("ALTER TABLE products ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER description");
    }
}

function local_bootstrap_database(string $host, string $name, string $user, string $pass, string $port): PDO
{
    $server = local_db_pdo('mysql:host=' . $host . ';port=' . $port . ';charset=utf8mb4', $user, $pass);
    $server->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo = local_db_pdo('mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4', $user, $pass);
    local_apply_schema($pdo);
    return $pdo;
}
