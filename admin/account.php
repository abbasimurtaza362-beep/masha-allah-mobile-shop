<?php
declare(strict_types=1);

$admin_title = 'Account Settings';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_guard.php';

$pdo = db();
$adminId = (int)($_SESSION['user']['id'] ?? 0);

function current_owner(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare("SELECT id,name,email,password_hash,role,session_version FROM users WHERE id=? AND role='admin' LIMIT 1");
    $stmt->execute([$id]);
    $owner = $stmt->fetch();
    if (!$owner) throw new RuntimeException('Administrator account could not be found.');
    return $owner;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $owner = current_owner($pdo, $adminId);
        $action = (string)($_POST['action'] ?? '');
        $currentPassword = (string)($_POST['current_password'] ?? '');

        if (!rate_limit_allow('admin_account_change', client_ip(), 5, 900, 1800)) {
            throw new RuntimeException('Too many security changes. Please wait before trying again.');
        }
        if ($currentPassword === '' || !password_verify($currentPassword, (string)$owner['password_hash'])) {
            throw new RuntimeException('Your current password is incorrect.');
        }

        if ($action === 'change_email') {
            $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            if (!$email || text_length($email) > 190) throw new RuntimeException('Enter a valid email address.');
            $exists = $pdo->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');
            $exists->execute([$email, $adminId]);
            if ($exists->fetch()) throw new RuntimeException('That email address is already in use.');
            $pdo->prepare('UPDATE users SET email=?,session_version=session_version+1 WHERE id=?')->execute([$email, $adminId]);
            $message = 'Administrator email updated. Other active sessions were signed out.';
        } elseif ($action === 'change_password') {
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');
            if (strlen($newPassword) < 12 || strlen($newPassword) > 72 || !hash_equals($newPassword, $confirmPassword)) {
                throw new RuntimeException('Use matching passwords between 12 and 72 characters.');
            }
            $pdo->prepare('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE id=?')->execute([password_hash($newPassword, PASSWORD_DEFAULT), $adminId]);
            $message = 'Administrator password updated. Other active sessions were signed out.';
        } else {
            throw new RuntimeException('Invalid account update request.');
        }

        rate_limit_reset('admin_account_change', client_ip());
        $freshOwner = current_owner($pdo, $adminId);
        begin_authenticated_session($freshOwner);
        flash('success', $message);
        redirect('admin/account.php');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('admin/account.php');
    }
}

$owner = current_owner($pdo, $adminId);
require __DIR__ . '/layout_top.php';
?>
<span class="eyebrow" style="color:#ae7d2c">Owner security</span><h1>Account settings</h1>
<p class="muted settings-intro">Change your private administrator email or password. Every change requires the current password and invalidates other active administrator sessions.</p>
<div class="settings-grid">
    <section class="form-card settings-card">
        <span class="eyebrow" style="color:#ae7d2c">Sign-in email</span><h3>Change email</h3>
        <p class="muted">Current email: <strong><?=e($owner['email'])?></strong></p>
        <form class="auth-form" method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="change_email">
            <label>New administrator email<input required type="email" name="email" autocomplete="email" maxlength="190" placeholder="owner@example.com"></label>
            <label>Current password<span class="password-control"><input required type="password" name="current_password" autocomplete="current-password"><button class="password-toggle" data-password-toggle type="button" aria-label="Show current password">Show</button></span></label>
            <button class="button dark" type="submit">Save new email</button>
        </form>
    </section>
    <section class="form-card settings-card">
        <span class="eyebrow" style="color:#ae7d2c">Password security</span><h3>Change password</h3>
        <p class="muted">Use a new unique password of 12–72 characters. Your saved password is never shown.</p>
        <form class="auth-form" method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="change_password">
            <label>Current password<span class="password-control"><input required type="password" name="current_password" autocomplete="current-password"><button class="password-toggle" data-password-toggle type="button" aria-label="Show current password">Show</button></span></label>
            <label>New password<span class="password-control"><input required type="password" name="new_password" autocomplete="new-password" minlength="12" maxlength="72"><button class="password-toggle" data-password-toggle type="button" aria-label="Show new password">Show</button></span></label>
            <label>Confirm new password<span class="password-control"><input required type="password" name="confirm_password" autocomplete="new-password" minlength="12" maxlength="72"><button class="password-toggle" data-password-toggle type="button" aria-label="Show confirmation password">Show</button></span></label>
            <button class="button dark" type="submit">Update password</button>
        </form>
    </section>
</div>
<?php require __DIR__ . '/layout_bottom.php'; ?>
