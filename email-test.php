<?php
declare(strict_types=1);
require_once __DIR__ . '/config/email.php';
require_once __DIR__ . '/includes/functions.php';
if (!in_array((string)($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1','::1'], true)) { http_response_code(404); exit('Not found.'); }
$ok = email_configured();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Email setup check</title><link rel="stylesheet" href="<?=e(url('assets/css/app.css'))?>"></head><body><main class="section"><div class="container"><div class="card"><span class="eyebrow">Local setup</span><h1>Email configuration</h1><p><strong>Status:</strong> <?= $ok ? 'Configured' : 'Not configured' ?></p><p>SMTP host: <?=e($SMTP_HOST)?><br>SMTP port: <?=e((string)$SMTP_PORT)?><br>SMTP user: <?=e($SMTP_USER !== '' ? 'Configured' : 'Missing')?><br>SMTP password: <?=e($SMTP_PASS !== '' ? 'Configured' : 'Missing')?><br>Sender: <?=e($SMTP_FROM_EMAIL !== '' ? $SMTP_FROM_EMAIL : 'Missing')?></p><p>Do not expose this page publicly. Delete <code>email-test.php</code> after setup.</p></div></div></main></body></html>