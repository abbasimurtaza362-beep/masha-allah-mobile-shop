<?php
declare(strict_types=1);

if (!defined('BASE_URL')) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = preg_replace('#/admin/[^/]+\.php$#', '', $script) ?? '';
    define('BASE_URL', rtrim($base, '/'));
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_guard.php';
$admin_title = $admin_title ?? 'Shop Management';
$adminUser = $_SESSION['user'] ?? ['name' => 'Administrator', 'email' => ''];
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!doctype html><html lang="en" class="ui-pending"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?=e($admin_title)?></title><style>html.ui-pending body{visibility:hidden;background:#f5f7f8}</style><link rel="stylesheet" href="<?=url('assets/css/app.css')?>"><script src="<?=url('assets/js/ui-ready.js')?>" defer></script></head><body class="admin-shell">
<?php require __DIR__ . '/../includes/chat.php'; ?>
<div class="admin-wrap"><aside class="admin-sidebar"><div class="admin-brand"><img src="<?=url('assets/images/logo.png')?>" alt=""><span><strong>Masha Allah Mobile</strong><small>OWNER CONSOLE</small></span></div><div class="admin-nav-label">Workspace</div>
<a class="admin-nav-link <?=$current==='dashboard.php'?'active':''?>" href="<?=url('admin/dashboard.php')?>"><span class="admin-nav-icon">⌂</span>Overview</a><a class="admin-nav-link <?=$current==='orders.php'?'active':''?>" href="<?=url('admin/orders.php')?>"><span class="admin-nav-icon">▤</span>Orders</a><a class="admin-nav-link <?=$current==='sales.php'?'active':''?>" href="<?=url('admin/sales.php')?>"><span class="admin-nav-icon">↗</span>Sales</a><a class="admin-nav-link <?=$current==='products.php'?'active':''?>" href="<?=url('admin/products.php')?>"><span class="admin-nav-icon">▣</span>Products</a><a class="admin-nav-link <?=$current==='inventory.php'?'active':''?>" href="<?=url('admin/inventory.php')?>"><span class="admin-nav-icon">≋</span>Inventory</a><a class="admin-nav-link <?=$current==='categories.php'?'active':''?>" href="<?=url('admin/categories.php')?>"><span class="admin-nav-icon">◫</span>Categories</a><a class="admin-nav-link <?=$current==='services.php'?'active':''?>" href="<?=url('admin/services.php')?>"><span class="admin-nav-icon">✦</span>Services</a><a class="admin-nav-link <?=$current==='messages.php'?'active':''?>" href="<?=url('admin/messages.php')?>"><span class="admin-nav-icon">✉</span>Inquiries</a><a class="admin-nav-link <?=$current==='users.php'?'active':''?>" href="<?=url('admin/users.php')?>"><span class="admin-nav-icon">◎</span>Customers</a><div class="admin-nav-label">Account</div><a class="admin-nav-link <?=$current==='account.php'?'active':''?>" href="<?=url('admin/account.php')?>"><span class="admin-nav-icon">⚙</span>Settings</a><div class="admin-nav-label">Quick access</div><a class="admin-nav-link" href="<?=url('products.php')?>" target="_blank"><span class="admin-nav-icon">↗</span>View storefront</a><a class="admin-nav-link" href="<?=url('admin/logout.php')?>"><span class="admin-nav-icon">⇥</span>Sign out</a></aside>
<main class="admin-main"><div class="admin-topbar"><div><span class="eyebrow" style="color:#ae7d2c">Private owner portal</span><h1 style="margin:4px 0 0;font-size:32px"><?=e($admin_title)?></h1></div><div class="admin-user"><div class="admin-avatar"><?=e(strtoupper(substr($adminUser['name']??'A',0,1)))?></div><div><strong><?=e($adminUser['name']??'Administrator')?></strong><div class="muted">Administrator</div></div></div></div><?php if($m=flash('success')):?><div class="flash success"><?=e($m)?></div><?php endif;?><?php if($m=flash('error')):?><div class="flash error"><?=e($m)?></div><?php endif;?>
