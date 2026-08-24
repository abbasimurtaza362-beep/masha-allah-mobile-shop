<?php
declare(strict_types=1);

$page_title = 'Create Account | Masha Allah Mobile';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/email.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = normalize_customer_email((string)($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $identifier = $email ?: client_ip();

    /*
     * Basic validation
     */
    if (
        text_length($name) < 2 ||
        text_length($name) > 100 ||
        !$email ||
        strlen($password) < 12 ||
        strlen($password) > 72 ||
        !hash_equals($password, $confirm)
    ) {
        flash(
            'error',
            'Enter a valid name and email, and use matching passwords between 12 and 72 characters.'
        );
    } elseif (!email_configured()) {
        flash(
            'error',
            'OTP could not be sent right now. Please try again later.'
        );
    } else {
        try {
            /*
             * Database connection
             */
            $pdo = db();

            /*
             * Check whether email already exists
             */
            $check = $pdo->prepare(
                "SELECT
                    id,
                    email,
                    email_verified,
                    last_otp_sent_at,
                    status,
                    TIMESTAMPDIFF(
                        SECOND,
                        last_otp_sent_at,
                        NOW()
                    ) AS otp_elapsed_seconds
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );

            $check->execute([$email]);
            $existing = $check->fetch();

            /*
             * Verified account already exists
             */
            if ($existing && (int)$existing['email_verified'] === 1) {
                throw new RuntimeException(
                    'Email already exists. Please login or use another email address.'
                );
            }

            /*
             * Account exists but is not active
             */
            if (
                $existing &&
                (($existing['status'] ?? 'active') !== 'active')
            ) {
                throw new RuntimeException(
                    'This account is currently unavailable. Please contact support.'
                );
            }

            /*
             * OTP resend protection
             */
            $elapsedSeconds = $existing
                ? max(0, (int)($existing['otp_elapsed_seconds'] ?? 0))
                : 0;

            $resendWait = (
                $existing &&
                !empty($existing['last_otp_sent_at'])
            )
                ? max(0, min(120, 120 - $elapsedSeconds))
                : 0;

            if ($resendWait > 0) {
                throw new RuntimeException(
                    'Please wait ' .
                    $resendWait .
                    ' seconds before requesting another OTP.'
                );
            }

            /*
             * Daily OTP rate limit
             */
            if (
                !rate_limit_allow(
                    'otp_daily_email',
                    (string)$email,
                    $OTP_DAILY_MAX_REQUESTS,
                    $OTP_DAILY_WINDOW_SECONDS,
                    $OTP_DAILY_WINDOW_SECONDS
                )
            ) {
                throw new RuntimeException(
                    'Daily OTP request limit reached for this email. Please try again tomorrow.'
                );
            }

            /*
             * Generate OTP
             */
            $otp = (string)random_int(100000, 999999);

            $hash = password_hash(
                $otp,
                PASSWORD_DEFAULT
            );

            $expiryExpression = $OTP_EXPIRY_MINUTES > 0
                ? 'DATE_ADD(NOW(), INTERVAL ' .
                  (int)$OTP_EXPIRY_MINUTES .
                  ' MINUTE)'
                : 'NULL';

            /*
             * Start database transaction
             */
            $pdo->beginTransaction();

            try {
                /*
                 * Existing unverified account
                 */
                if ($existing) {
                    $stmt = $pdo->prepare(
                        "UPDATE users
                         SET
                            name = ?,
                            password_hash = ?,
                            role = 'customer',
                            email_verified = 0,
                            otp_hash = ?,
                            otp_expires_at = {$expiryExpression},
                            otp_attempts = 0,
                            last_otp_sent_at = NOW()
                         WHERE id = ?"
                    );

                    $stmt->execute([
                        $name,
                        password_hash($password, PASSWORD_DEFAULT),
                        $hash,
                        $existing['id']
                    ]);
                } else {
                    /*
                     * New account
                     */
                    $stmt = $pdo->prepare(
                        "INSERT INTO users
                        (
                            name,
                            email,
                            password_hash,
                            role,
                            email_verified,
                            otp_hash,
                            otp_expires_at,
                            otp_attempts,
                            last_otp_sent_at
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            'customer',
                            0,
                            ?,
                            {$expiryExpression},
                            0,
                            NOW()
                        )"
                    );

                    $stmt->execute([
                        $name,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $hash
                    ]);
                }

                /*
                 * Load OTP email handler
                 */
                $emailHandler = __DIR__ . '/includes/email.php';

                if (!is_file($emailHandler)) {
                    throw new RuntimeException(
                        'OTP email handler file is missing: includes/email.php'
                    );
                }

                require_once $emailHandler;

                /*
                 * Make sure the function actually exists
                 */
                if (!function_exists('send_otp_email')) {
                    throw new RuntimeException(
                        'OTP email handler loaded, but send_otp_email() is missing.'
                    );
                }

                /*
                 * Send OTP
                 */
                send_otp_email(
                    $email,
                    $name,
                    $otp
                );

                /*
                 * Everything succeeded
                 */
                $pdo->commit();

            } catch (Throwable $mailError) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                /*
                 * Keep the real error in Railway logs
                 */
                error_log(
                    'OTP PROCESS ERROR: ' .
                    $mailError->getMessage()
                );

                throw $mailError;
            }

            /*
             * Regenerate session
             */
            session_regenerate_id(true);

            $_SESSION['csrf'] = bin2hex(
                random_bytes(32)
            );

            $_SESSION['pending_verification_email'] = $email;

            flash(
                'success',
                'OTP sent. Please check your Inbox or Junk/Spam folder, then enter the OTP to verify your account.'
            );

            redirect('verify.php');

        } catch (Throwable $e) {

            /*
             * Always log the actual error in Railway
             */
            error_log(
                'OTP signup send failed: ' .
                $e->getMessage()
            );

            /*
             * User-friendly errors
             */
            $safeErrors = [
                'Email already exists. Please login or use another email address.',
                'This account is currently unavailable. Please contact support.',
                'Daily OTP request limit reached for this email. Please try again tomorrow.'
            ];

            $message = $e->getMessage();

            /*
             * Handle resend wait message separately
             */
            if (
                strpos(
                    $message,
                    'Please wait '
                ) === 0
            ) {
                $userMessage = $message;
            } elseif (
                in_array(
                    $message,
                    $safeErrors,
                    true
                )
            ) {
                $userMessage = $message;
            } else {
                /*
                 * Never expose SMTP/database secrets to the user
                 */
                $userMessage =
                    'OTP could not be sent right now. Please try again later.';
            }

            flash(
                'error',
                $userMessage
            );
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">

    <section class="auth-brand-panel auth-brand-panel-signup">

        <div class="auth-brand-inner">

            <a class="auth-logo" href="<?=url()?>">
                <img
                    src="<?=url('assets/images/logo.png')?>"
                    alt="Masha Allah Mobile logo"
                >

                <span>
                    <b>Masha Allah Mobile</b>
                    <small>&amp; EASYPAISA SHOP</small>
                </span>
            </a>

            <div class="auth-copy">

                <span class="eyebrow">
                    Join the shop
                </span>

                <h1>
                    Create your account.<br>
                    <em>Stay connected.</em>
                </h1>

                <p>
                    Get a secure customer account for smoother communication,
                    product enquiries and future online services.
                </p>

                <div class="auth-points">
                    <span>✓ Email verification with OTP</span>
                    <span>✓ Secure password protection</span>
                </div>

            </div>

            <a class="auth-back" href="<?=url()?>">
                ← Back to website
            </a>

        </div>

    </section>

    <section class="auth-form-panel">

        <div class="auth-form-wrap">

            <span class="eyebrow">
                New customer
            </span>

            <h2>
                Create your account
            </h2>

            <p class="muted">
                We'll send a one-time verification code to your email.
            </p>

            <form
                class="auth-form"
                method="post"
            >

                <input
                    type="hidden"
                    name="csrf"
                    value="<?=csrf_token()?>"
                >

                <label>
                    Full name

                    <input
                        autocomplete="name"
                        required
                        maxlength="100"
                        name="name"
                        value="<?=e($_POST['name'] ?? '')?>"
                        placeholder="Your name"
                    >
                </label>

                <label>
                    Email address

                    <input
                        autocomplete="email"
                        required
                        maxlength="190"
                        type="email"
                        name="email"
                        value="<?=e($_POST['email'] ?? '')?>"
                        placeholder="you@example.com"
                    >
                </label>

                <label>
                    Password

                    <span class="password-control">

                        <input
                            autocomplete="new-password"
                            required
                            minlength="12"
                            maxlength="72"
                            type="password"
                            name="password"
                            placeholder="12–72 characters"
                        >

                        <button
                            class="password-toggle"
                            data-password-toggle
                            type="button"
                            aria-label="Show password"
                        >
                            Show
                        </button>

                    </span>
                </label>

                <label>
                    Confirm password

                    <span class="password-control">

                        <input
                            autocomplete="new-password"
                            required
                            minlength="12"
                            maxlength="72"
                            type="password"
                            name="confirm_password"
                            placeholder="Repeat your password"
                        >

                        <button
                            class="password-toggle"
                            data-password-toggle
                            type="button"
                            aria-label="Show confirmation password"
                        >
                            Show
                        </button>

                    </span>
                </label>

                <button
                    class="button gold auth-submit"
                    type="submit"
                >
                    Create account
                </button>

            </form>

            <p class="auth-switch">
                Already registered?
                <a href="<?=url('login.php')?>">
                    Sign in
                </a>
            </p>

        </div>

    </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
