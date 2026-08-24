<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('verify.php');
verify_csrf();
$email = $_SESSION['pending_verification_email'] ?? '';
if (!$email) redirect('signup.php');

$pdo = db();
$stmt = $pdo->prepare("SELECT *, TIMESTAMPDIFF(SECOND,last_otp_sent_at,NOW()) AS otp_elapsed_seconds FROM users WHERE email=? AND role='customer' LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user) {
    flash('error', 'Verification account not found.');
    redirect('signup.php');
}

$elapsedSeconds = max(0, (int)($user['otp_elapsed_seconds'] ?? 0));
$remaining = !empty($user['last_otp_sent_at']) ? max(0, min(120, 120 - $elapsedSeconds)) : 0;
if ($remaining > 0) {
    flash('error', 'Please wait ' . $remaining . ' seconds before requesting another OTP.');
    redirect('verify.php');
}
if (!email_configured()) {
    flash('error', 'OTP could not be sent right now. Please try again later.');
    redirect('verify.php');
}
if (!rate_limit_allow('otp_daily_email', (string)$user['email'], $OTP_DAILY_MAX_REQUESTS, $OTP_DAILY_WINDOW_SECONDS, $OTP_DAILY_WINDOW_SECONDS)) {
    flash('error', 'Daily OTP request limit reached for this email. Please try again tomorrow.');
    redirect('verify.php');
}
$otp = (string)random_int(100000, 999999);
$expiryExpression = $OTP_EXPIRY_MINUTES > 0 ? 'DATE_ADD(NOW(), INTERVAL ' . (int)$OTP_EXPIRY_MINUTES . ' MINUTE)' : 'NULL';
try {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE users SET otp_hash=?,otp_expires_at={$expiryExpression},otp_attempts=0,last_otp_sent_at=NOW() WHERE id=?")->execute([password_hash($otp, PASSWORD_DEFAULT), $user['id']]);
    send_otp_email($user['email'], $user['name'], $otp);
    $pdo->commit();
    flash('success', 'OTP sent. Please check your Inbox or Junk/Spam folder, then enter the OTP to verify your account.');
} catch (Throwable $e) {
    error_log('OTP resend failed: ' . $e->getMessage());
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', 'OTP could not be sent right now. Please try again later.');
}
redirect('verify.php');
