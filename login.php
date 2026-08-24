<?php
$page_title='Customer Login | Masha Allah Mobile';
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/header.php';
if (is_logged_in()) redirect(is_admin() ? 'admin/dashboard.php' : 'index.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $identifier = $email ?: client_ip();
    if (!rate_limit_allow('customer_login_ip', client_ip(), 8, 900, 900) || !rate_limit_allow('customer_login_email', $identifier, 8, 900, 900)) {
        flash('error', 'Too many sign-in attempts. Please wait before trying again.');
    } else {
        if (!$email || text_length($email) > 190 || $password === '' || strlen($password) > 72) {
            flash('error', 'Invalid email or password.');
        } else {
            $stmt = db()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) {
                flash('error', 'Invalid email or password.');
            } elseif (($user['status'] ?? 'active') !== 'active') {
                flash('error', 'Invalid email or password.');
            } elseif (($user['role'] ?? '') !== 'customer') {
                // Keep the public login response generic for every non-customer account.
                flash('error', 'Invalid email or password.');
            } elseif (!(int)$user['email_verified']) {
                $_SESSION['pending_verification_email'] = $user['email'];
                flash('error', 'Please verify your email before signing in.');
                redirect('verify.php');
            } else {
                rate_limit_reset('customer_login_ip', client_ip());
                rate_limit_reset('customer_login_email', $identifier);
                begin_authenticated_session($user);
                flash('success', 'Welcome back, ' . $user['name'] . '.');
                redirect('index.php');
            }
        }
    }
}
?>
<main class="auth-page"><section class="auth-brand-panel"><div class="auth-brand-inner"><a class="auth-logo" href="<?=url()?>"><img src="<?=url('assets/images/logo.png')?>" alt="Masha Allah Mobile logo"><span><b>Masha Allah Mobile</b><small>&amp; EASYPAISA SHOP</small></span></a><div class="auth-copy"><span class="eyebrow">Trusted local mobile shop</span><h1>Everything mobile.<br><em>One trusted place.</em></h1><p>Browse accessories, explore services and stay connected with Masha Allah Mobile &amp; EasyPaisa Shop in Quetta.</p><div class="auth-points"><span>✓ Genuine-looking catalogue experience</span><span>✓ Fast local support</span><span>✓ Secure customer accounts</span></div></div><a class="auth-back" href="<?=url()?>">← Back to website</a></div></section><section class="auth-form-panel"><div class="auth-form-wrap"><div class="mobile-auth-brand"><span class="eyebrow">Customer account</span></div><span class="eyebrow">Welcome back</span><h2>Sign in to your account</h2><p class="muted">Access your customer account securely.</p><form class="auth-form" method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><label>Email address<input autocomplete="email" required type="email" name="email" value="<?=e($_POST['email']??'')?>" placeholder="you@example.com"></label><label>Password<span class="password-control"><input autocomplete="current-password" required type="password" name="password" placeholder="Enter your password"><button class="password-toggle" data-password-toggle type="button" aria-label="Show password">Show</button></span></label><button class="button dark auth-submit" type="submit">Sign in</button></form><p class="auth-switch">Don't have an account? <a href="<?=url('signup.php')?>">Create one</a></p><div class="auth-note">Your account is separate from the shop's private administrator area.</div></div></section></main>
<?php require __DIR__.'/includes/footer.php'; ?>
