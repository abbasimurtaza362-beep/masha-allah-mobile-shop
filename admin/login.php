<?php
$page_title='Administrator Login | Masha Allah Mobile';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/functions.php';
if (is_admin()) redirect('admin/dashboard.php');
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $email=filter_var(trim($_POST['email']??''),FILTER_VALIDATE_EMAIL); $password=$_POST['password']??'';
    // Admin login is intentionally not blocked by the public rate-limit buckets.
    // CSRF validation, email validation and password verification remain active.
    if (!$email || text_length($email) > 190 || $password === '' || strlen($password) > 72) {
        flash('error','Invalid administrator credentials.');
    } else {
        $stmt=db()->prepare("SELECT * FROM users WHERE email=? AND role='admin' AND status='active' LIMIT 1"); $stmt->execute([$email]); $user=$stmt->fetch();
        if($user && password_verify($password,$user['password_hash'])){
            begin_authenticated_session($user); redirect('admin/dashboard.php');
        }
        flash('error','Invalid administrator credentials.');
    }
}
?>
<!doctype html><html lang="en" class="ui-pending"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($page_title)?></title><style>html.ui-pending body{visibility:hidden;background:#f5f7f8}</style><link rel="stylesheet" href="<?=url('assets/css/app.css')?>"><script src="<?=url('assets/js/ui-ready.js')?>" defer></script></head><body><main class="auth-page admin-auth-page"><section class="auth-brand-panel"><div class="auth-brand-inner"><a class="auth-logo" href="<?=url()?>"><img src="<?=url('assets/images/logo.png')?>" alt="Masha Allah Mobile logo"><span><b>Masha Allah Mobile</b><small>PRIVATE OWNER PORTAL</small></span></a><div class="auth-copy"><span class="eyebrow">Private administration</span><h1>Run the shop.<br><em>Behind the scenes.</em></h1><p>This secure portal is intentionally separated from the public website. Manage inventory, services, inquiries and shop operations here.</p><div class="auth-points"><span>✓ Private administrator route</span><span>✓ Inventory &amp; stock controls</span><span>✓ Customer inquiry management</span></div></div><a class="auth-back" href="<?=url()?>">← Back to website</a></div></section><section class="auth-form-panel"><div class="auth-form-wrap"><span class="eyebrow">Owner portal</span><h2>Administrator sign in</h2><p class="muted">This page is not linked from the public navigation.</p><?php if($m=flash('error')):?><div class="flash error"><?=e($m)?></div><?php endif;?><form class="auth-form" method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><label>Administrator email<input required type="email" name="email" autocomplete="username"></label><label>Password<span class="password-control"><input required type="password" name="password" autocomplete="current-password"><button class="password-toggle" data-password-toggle type="button" aria-label="Show password">Show</button></span></label><button class="button dark auth-submit" type="submit">Enter admin panel</button></form><div class="auth-note">Keep this route private. Never publish administrator credentials in your project screenshots or report.</div></div></section></main><?php require __DIR__ . '/../includes/chat.php'; ?><script src="<?=url('assets/js/auth-ui.js')?>" defer></script></body></html>
