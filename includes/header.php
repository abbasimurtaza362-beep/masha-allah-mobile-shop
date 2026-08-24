<?php
if (!defined('BASE_URL')) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = preg_replace('#/(?:admin/)?[^/]+\.php$#', '', $script) ?? '';
    define('BASE_URL', rtrim($base, '/'));
}
require_once __DIR__ . '/functions.php';
if (function_exists('db')) { require_once __DIR__ . '/catalog.php'; try { auto_seed_catalog(db()); } catch (Throwable $e) { /* catalogue remains available even if seeding cannot run */ } }
$page_title = $page_title ?? 'Masha Allah Mobile & EasyPaisa Shop';
?>
<!doctype html><html lang="en" class="ui-pending"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Masha Allah Mobile & EasyPaisa Shop in Quetta — accessories, repairs, software, EasyPaisa and JazzCash services."><title><?= e($page_title) ?></title><style>html.ui-pending body{visibility:hidden;background:#f5f7f8}</style><link rel="icon" type="image/x-icon" href="<?= url('assets/images/favicon.ico') ?>"><link rel="stylesheet" href="<?= url('assets/css/app.css') ?>"><script src="<?=url('assets/js/ui-ready.js')?>" defer></script></head><body>
<?php require __DIR__ . '/chat.php'; ?>
<header class="site-header"><div class="container nav"><a class="brand" href="<?= url() ?>"><img src="<?= url('assets/images/logo.png') ?>" alt="Masha Allah Mobile & EasyPaisa Shop logo"><span><b>Masha Allah Mobile</b><small>&amp; EASYPAISA SHOP</small></span></a><nav aria-label="Primary navigation"><a href="<?= url() ?>">Home</a><a href="<?= url('products.php') ?>">Products</a><a href="<?= url('services.php') ?>">Services</a><a href="<?= url('contact.php') ?>">Contact</a><a href="<?= url('about.php') ?>">About</a><?php if(is_logged_in()): ?><a href="<?= url('logout.php') ?>">Logout</a><?php else: ?><a href="<?= url('login.php') ?>">Sign in</a><a class="button gold" style="min-height:34px;padding:7px 12px" href="<?= url('signup.php') ?>">Sign up</a><?php endif; ?></nav></div></header>
<?php if ($message = flash('success')): ?><div class="flash success"><?= e($message) ?></div><?php endif; ?><?php if ($message = flash('error')): ?><div class="flash error"><?= e($message) ?></div><?php endif; ?>
