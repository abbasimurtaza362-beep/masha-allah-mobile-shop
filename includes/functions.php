<?php
declare(strict_types=1);

if (!ob_get_level()) ob_start();

// Derive the application root once. Works for normal pages, admin pages and POST-only endpoints.
if (!defined('BASE_URL')) {
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $root = preg_replace('#/(?:admin/|api/)?[^/]+(?:/index)?\.php$#', '', $script);
    if (!is_string($root)) $root = '';
    define('BASE_URL', rtrim($root, '/'));
}

function app_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function enforce_https_if_configured(): void
{
    if (getenv('APP_FORCE_HTTPS') !== '1' || app_is_https() || headers_sent()) return;

    $host = trim((string)(getenv('APP_PUBLIC_HOST') ?: ''));
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if ($host === '' || !preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $host) || !str_starts_with($uri, '/')) return;

    header('Location: https://' . $host . $uri, true, 302);
    exit;
}

function session_cookie_options(): array
{
    return [
        'lifetime' => 0,
        'path' => '/',
        'secure' => app_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function send_security_headers(): void
{
    if (headers_sent()) return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), geolocation=(), microphone=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; font-src 'self' data:; script-src 'self'; connect-src 'self' https://api.x.ai https://api.groq.com; frame-src https://www.google.com https://maps.google.com");

    if (app_is_https()) header('Strict-Transport-Security: max-age=15552000');
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;

    enforce_https_if_configured();
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', app_is_https() ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    session_name('masha_session');
    session_set_cookie_params(session_cookie_options());
    session_cache_limiter('nocache');
    session_start();
}

function reset_session(): void
{
    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        if (ini_get('session.use_cookies')) {
            $options = session_cookie_options();
            unset($options['lifetime']);
            $options['expires'] = time() - 42000;
            setcookie(session_name(), '', $options);
        }
        session_destroy();
    }

    session_id('');
    start_secure_session();
    session_regenerate_id(true);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function expire_session_if_needed(): void
{
    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) return;

    $now = time();
    $role = $_SESSION['user']['role'] ?? 'customer';
    $idleLimit = $role === 'admin' ? 1800 : 7200;
    $absoluteLimit = 28800;
    $issuedAt = (int)($_SESSION['issued_at'] ?? 0);
    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);

    if ($issuedAt === 0 || $lastActivity === 0 || ($now - $lastActivity) > $idleLimit || ($now - $issuedAt) > $absoluteLimit) {
        $loginPath = $role === 'admin' ? 'admin/login.php' : 'login.php';
        reset_session();
        flash('error', 'Your session has expired. Please sign in again.');
        redirect($loginPath);
    }

    $_SESSION['last_activity'] = $now;

    // A blocked customer should be logged out on the next request, even if
    // the session was created before the administrator blocked the account.
    if ($role === 'customer' && function_exists('db')) {
        try {
            $stmt = db()->prepare("SELECT status, session_version FROM users WHERE id=? AND role='customer' LIMIT 1");
            $stmt->execute([(int)($_SESSION['user']['id'] ?? 0)]);
            $freshUser = $stmt->fetch();
            if (!$freshUser || ($freshUser['status'] ?? 'active') !== 'active' || !hash_equals((string)($freshUser['session_version'] ?? ''), (string)($_SESSION['user']['session_version'] ?? ''))) {
                reset_session();
                flash('error', 'Your account is not available.');
                redirect('login.php');
            }
        } catch (Throwable $error) {
            // Keep the existing session if a temporary database check fails.
        }
    }
}

start_secure_session();
send_security_headers();
expire_session_if_needed();

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path), true, 302); exit; }
function is_logged_in(): bool { return isset($_SESSION['user']) && is_array($_SESSION['user']); }
function is_admin(): bool { return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'admin'; }

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $token = (string)($_POST['csrf'] ?? '');
    if ($token === '' || !hash_equals((string)($_SESSION['csrf'] ?? ''), $token)) {
        http_response_code(419);
        exit('Invalid request token. Please go back and try again.');
    }
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

function begin_authenticated_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $_SESSION['issued_at'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => (string)$user['name'],
        'email' => (string)$user['email'],
        'role' => (string)$user['role'],
        'session_version' => (int)($user['session_version'] ?? 1),
    ];
}

function logout_user(): void
{
    reset_session();
}

function client_ip(): string
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

function rate_limit_fallback(string $bucket, int $maxAttempts, int $windowSeconds, int $blockSeconds): bool
{
    $now = time();
    $state = $_SESSION['rate_limits'][$bucket] ?? ['count' => 0, 'started' => $now, 'blocked_until' => 0];
    if ((int)$state['blocked_until'] > $now) return false;
    if ($now - (int)$state['started'] >= $windowSeconds) $state = ['count' => 0, 'started' => $now, 'blocked_until' => 0];

    $state['count']++;
    if ((int)$state['count'] > $maxAttempts) {
        $state['blocked_until'] = $now + $blockSeconds;
        $_SESSION['rate_limits'][$bucket] = $state;
        return false;
    }

    $_SESSION['rate_limits'][$bucket] = $state;
    return true;
}

function normalize_rate_identifier(string $identifier): string
{
    $identifier = trim($identifier);
    return function_exists('mb_strtolower') ? mb_strtolower($identifier) : strtolower($identifier);
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function normalize_customer_email(string $value): ?string
{
    $email = strtolower(trim($value));
    if ($email === '' || strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) return null;

    $at = strrpos($email, '@');
    if ($at === false) return null;
    $domain = substr($email, $at + 1);
    $labels = explode('.', $domain);
    if (count($labels) < 2 || strlen(end($labels)) < 2) return null;

    foreach ($labels as $label) {
        if ($label === '' || strlen($label) > 63 || $label[0] === '-' || substr($label, -1) === '-' || !preg_match('/^[a-z0-9-]+$/', $label)) return null;
    }
    return $email;
}

function text_slice(string $value, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
}

function rate_limit_allow(string $context, string $identifier, int $maxAttempts, int $windowSeconds, int $blockSeconds): bool
{
    $bucket = hash('sha256', $context . '|' . normalize_rate_identifier($identifier));

    try {
        $pdo = db();
        $pdo->beginTransaction();
        $select = $pdo->prepare('SELECT attempts, window_started_at, blocked_until FROM security_rate_limits WHERE bucket_hash=? FOR UPDATE');
        $select->execute([$bucket]);
        $record = $select->fetch();
        $now = time();
        $allowed = true;

        if (!$record) {
            $pdo->prepare('INSERT INTO security_rate_limits(bucket_hash,attempts,window_started_at,blocked_until) VALUES(?,1,NOW(),NULL)')->execute([$bucket]);
        } else {
            $blockedUntil = !empty($record['blocked_until']) ? strtotime((string)$record['blocked_until']) : 0;
            $windowStarted = strtotime((string)$record['window_started_at']) ?: 0;

            if ($blockedUntil > $now) {
                $allowed = false;
            } elseif ($now - $windowStarted >= $windowSeconds) {
                $pdo->prepare('UPDATE security_rate_limits SET attempts=1,window_started_at=NOW(),blocked_until=NULL WHERE bucket_hash=?')->execute([$bucket]);
            } else {
                $attempts = (int)$record['attempts'] + 1;
                if ($attempts > $maxAttempts) {
                    $blockedUntil = date('Y-m-d H:i:s', $now + $blockSeconds);
                    $pdo->prepare('UPDATE security_rate_limits SET attempts=?,blocked_until=? WHERE bucket_hash=?')->execute([$attempts, $blockedUntil, $bucket]);
                    $allowed = false;
                } else {
                    $pdo->prepare('UPDATE security_rate_limits SET attempts=? WHERE bucket_hash=?')->execute([$attempts, $bucket]);
                }
            }
        }

        $pdo->commit();
        return $allowed;
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        return rate_limit_fallback($bucket, $maxAttempts, $windowSeconds, $blockSeconds);
    }
}

function rate_limit_reset(string $context, string $identifier): void
{
    $bucket = hash('sha256', $context . '|' . normalize_rate_identifier($identifier));
    unset($_SESSION['rate_limits'][$bucket]);
    try {
        db()->prepare('DELETE FROM security_rate_limits WHERE bucket_hash=?')->execute([$bucket]);
    } catch (Throwable $e) {
        // Fallback state was already cleared; preserve normal user flow if the table is unavailable.
    }
}

function current_admin_session_is_valid(): bool
{
    if (!is_admin()) return false;

    try {
        $stmt = db()->prepare("SELECT role,status,session_version FROM users WHERE id=? LIMIT 1");
        $stmt->execute([(int)$_SESSION['user']['id']]);
        $user = $stmt->fetch();
        return $user
            && $user['role'] === 'admin'
            && ($user['status'] ?? 'active') === 'active'
            && hash_equals((string)($user['session_version'] ?? ''), (string)($_SESSION['user']['session_version'] ?? ''));
    } catch (Throwable $e) {
        return false;
    }
}

function same_origin_browser_request(): bool
{
    $fetchSite = strtolower((string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
    if (in_array($fetchSite, ['cross-site', 'none'], true)) return false;

    $origin = rtrim((string)($_SERVER['HTTP_ORIGIN'] ?? ''), '/');
    if ($origin === '') return true;

    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '' || !preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $host)) return false;

    $originParts = parse_url($origin);
    if (!is_array($originParts) || !in_array(strtolower((string)($originParts['scheme'] ?? '')), ['http', 'https'], true) || empty($originParts['host'])) return false;
    $originHost = (string)$originParts['host'] . (isset($originParts['port']) ? ':' . (int)$originParts['port'] : '');

    // The browser may reach a TLS-terminating proxy while PHP receives plain HTTP.
    // Comparing the validated host, rather than PHP's internal scheme, keeps same-site
    // requests working without accepting requests from another origin host.
    return hash_equals(strtolower($host), strtolower($originHost));
}

function stock_label(int $quantity): string { return $quantity === 0 ? 'Out of stock' : ($quantity <= 5 ? 'Low stock' : 'In stock'); }
function stock_class(int $quantity): string { return $quantity === 0 ? 'stock-out' : ($quantity <= 5 ? 'stock-low' : 'stock-in'); }

function upload_image(string $field, string $directory = 'products'): ?string
{
    if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int)($_FILES[$field]['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new RuntimeException('Image upload failed or exceeds 4 MB.');
    }

    $allowedDirectories = ['products', 'services'];
    if (!in_array($directory, $allowedDirectories, true)) throw new RuntimeException('Invalid image destination.');

    $tmpFile = (string)($_FILES[$field]['tmp_name'] ?? '');
    if ($tmpFile === '' || !is_uploaded_file($tmpFile)) throw new RuntimeException('Invalid image upload.');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpFile);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $imageInfo = @getimagesize($tmpFile);
    if (!isset($extensions[$mime]) || !$imageInfo) throw new RuntimeException('Upload a valid JPG, PNG, or WEBP image.');

    $width = (int)($imageInfo[0] ?? 0);
    $height = (int)($imageInfo[1] ?? 0);
    if ($width < 1 || $height < 1 || $width * $height > 16000000) {
        throw new RuntimeException('Image dimensions are too large. Use an image up to 16 megapixels.');
    }

    $folder = __DIR__ . '/../uploads/' . $directory;
    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) throw new RuntimeException('Cannot create upload directory.');

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($tmpFile, $folder . '/' . $filename)) throw new RuntimeException('Could not save the uploaded image.');
    return 'uploads/' . $directory . '/' . $filename;
}
