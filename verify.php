<?php
declare(strict_types=1);

$page_title = 'Verify Email | Masha Allah Mobile';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';
$email = $_SESSION['pending_verification_email'] ?? '';
if (!$email) redirect('signup.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $otp = preg_replace('/\D/', '', $_POST['otp'] ?? '');
        $stmt = db()->prepare("SELECT *, TIMESTAMPDIFF(SECOND,NOW(),otp_expires_at) AS otp_seconds_remaining FROM users WHERE email=? AND role='customer' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) {
            unset($_SESSION['pending_verification_email']);
            flash('error', 'Verification could not be completed.');
            redirect('signup.php');
        } elseif ((int)$user['email_verified']) {
            unset($_SESSION['pending_verification_email']);
            redirect('login.php');
        } elseif (!$user['otp_hash'] || !$user['otp_expires_at'] || (int)($user['otp_seconds_remaining'] ?? 0) <= 0) {
            flash('error', 'This code has expired. Request a new one.');
        } elseif (!password_verify($otp, $user['otp_hash'])) {
            flash('error', 'Incorrect verification code.');
        } else {
            $pdo = db();
            $pdo->prepare('UPDATE users SET email_verified=1,otp_hash=NULL,otp_expires_at=NULL,otp_attempts=0,last_otp_sent_at=NULL WHERE id=?')->execute([$user['id']]);
            unset($_SESSION['pending_verification_email']);
            begin_authenticated_session($user);
            flash('success', 'Email verified successfully. You are now signed in.');
            redirect('index.php');
        }
}

$stmt = db()->prepare("SELECT last_otp_sent_at, otp_hash, otp_expires_at, TIMESTAMPDIFF(SECOND,last_otp_sent_at,NOW()) AS otp_elapsed_seconds, TIMESTAMPDIFF(SECOND,NOW(),otp_expires_at) AS otp_seconds_remaining FROM users WHERE email=? AND role='customer' LIMIT 1");
$stmt->execute([$email]);
$verificationAccount = $stmt->fetch() ?: [];
$elapsedSeconds = max(0, (int)($verificationAccount['otp_elapsed_seconds'] ?? 0));
$resendSeconds = !empty($verificationAccount['last_otp_sent_at']) ? max(0, min(120, 120 - $elapsedSeconds)) : 0;
$hasActiveCode = !empty($verificationAccount['otp_hash']);
$codeExpired = $hasActiveCode && (empty($verificationAccount['otp_expires_at']) || (int)($verificationAccount['otp_seconds_remaining'] ?? 0) <= 0);
$emailReady = email_configured();
require_once __DIR__ . '/includes/header.php';
?>
<main class="auth-page"><section class="auth-brand-panel"><div class="auth-brand-inner"><a class="auth-logo" href="<?=url()?>"><img src="<?=url('assets/images/logo.png')?>" alt="Masha Allah Mobile logo"><span><b>Masha Allah Mobile</b><small>&amp; EASYPAISA SHOP</small></span></a><div class="auth-copy"><span class="eyebrow">Secure verification</span><h1>One quick step.<br><em>Then you're in.</em></h1><p>We use email verification to keep customer accounts secure and reduce fake registrations.</p><div class="auth-points"><span>✓ 6-digit one-time code</span><span>✓ Code expires in 10 minutes</span><span>✓ Protected customer account</span></div></div><a class="auth-back" href="<?=url()?>">← Back to website</a></div></section><section class="auth-form-panel"><div class="auth-form-wrap"><span class="eyebrow">Email verification</span><h2>Enter your code</h2><?php if (!$emailReady): ?><p class="muted">OTP is temporarily unavailable. Please try again later.</p><?php elseif ($codeExpired): ?><p class="muted">This verification code expired after 10 minutes. Request a new code below, then check Inbox and Spam.</p><?php elseif ($hasActiveCode): ?><p class="muted">Enter the OTP from your Inbox or Junk/Spam folder. After 2 minutes, you can request a fresh code even if this one has not expired.</p><?php else: ?><p class="muted">Request a new verification code for <strong><?=e($email)?></strong>.</p><?php endif; ?><form class="auth-form" method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><label>6-digit verification code<input inputmode="numeric" pattern="[0-9]{6}" maxlength="6" minlength="6" required name="otp" autocomplete="one-time-code" placeholder="000000"></label><button class="button dark auth-submit" type="submit">Verify email</button></form><form method="post" action="<?=url('resend.php')?>" class="otp-resend"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><button class="button outline-dark otp-resend-button" type="submit" data-otp-resend data-resend-seconds="<?=$resendSeconds?>" <?=$emailReady && $resendSeconds === 0 ? '' : 'disabled'?>><?php if (!$emailReady): ?>Try again later<?php elseif ($resendSeconds > 0): ?>Resend code in <span data-otp-countdown aria-live="polite"><?=gmdate('i:s', $resendSeconds)?></span><?php else: ?>Resend code<?php endif; ?></button></form><p class="auth-switch"><a href="<?=url('signup.php')?>">Use a different email</a></p></div></section></main>
<?php require __DIR__ . '/includes/footer.php'; ?>
