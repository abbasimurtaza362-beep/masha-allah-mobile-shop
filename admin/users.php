<?php
declare(strict_types=1);

$admin_title = 'Customers';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_guard.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $customerId = (int)($_POST['customer_id'] ?? 0);

    $customerStmt = $pdo->prepare("SELECT id, name, email, status FROM users WHERE id=? AND role='customer' LIMIT 1");
    $customerStmt->execute([$customerId]);
    $customer = $customerStmt->fetch();

    if (!$customer) {
        flash('error', 'Customer account was not found.');
    } elseif ($action === 'toggle_status') {
        $newStatus = ($customer['status'] ?? 'active') === 'blocked' ? 'active' : 'blocked';
        $stmt = $pdo->prepare("UPDATE users SET status=?, session_version=session_version+1 WHERE id=? AND role='customer'");
        $stmt->execute([$newStatus, $customerId]);
        flash('success', $newStatus === 'blocked' ? 'Customer account blocked.' : 'Customer account unblocked.');
    } elseif ($action === 'delete') {
        // Orders and inquiries are independent historical records and remain intact.
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND role='customer'");
        $stmt->execute([$customerId]);
        flash('success', 'Customer account deleted. Historical shop records were kept.');
    } else {
        flash('error', 'Invalid customer action.');
    }

    redirect('admin/users.php');
}

$users = $pdo->query("SELECT id,name,email,email_verified,status,created_at FROM users WHERE role='customer' AND email_verified=1 ORDER BY created_at DESC")->fetchAll();
require __DIR__ . '/layout_top.php';
?>
<section class="admin-panel"><div class="admin-panel-head"><div><h3>Registered customers</h3><p class="muted">Block access temporarily or delete a customer account. Orders and inquiries stay preserved.</p></div><span class="badge stock-in"><?=count($users)?> accounts</span></div><div class="table-wrap"><table class="admin-table customer-table"><thead><tr><th>Customer</th><th>Email</th><th>Verification</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead><tbody><?php if(!$users):?><tr><td colspan="6">No customer accounts yet.</td></tr><?php endif;?><?php foreach($users as $u):?><tr><td><strong><?=e($u['name'])?></strong></td><td><?=e($u['email'])?></td><td><span class="badge stock-in">Verified</span></td><td><?php if(($u['status']??'active')==='blocked'):?><span class="badge stock-low">Blocked</span><?php else:?><span class="badge stock-in">Active</span><?php endif;?></td><td><?=e(date('d M, Y',strtotime($u['created_at'])))?></td><td><div class="customer-actions"><form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="customer_id" value="<?=e((string)$u['id'])?>"><input type="hidden" name="action" value="toggle_status"><button class="button outline-dark button-small" type="submit"><?=($u['status']??'active')==='blocked'?'Unblock':'Block'?></button></form><form method="post" onsubmit="return confirm('Delete this customer account? Historical orders and inquiries will remain.');"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="customer_id" value="<?=e((string)$u['id'])?>"><input type="hidden" name="action" value="delete"><button class="button danger button-small" type="submit">Delete</button></form></div></td></tr><?php endforeach;?></tbody></table></div></section>
<?php require __DIR__.'/layout_bottom.php'; ?>
