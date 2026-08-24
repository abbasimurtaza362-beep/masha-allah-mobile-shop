<?php
declare(strict_types=1);

$currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isAdminRoute = str_contains($currentScript, '/admin/');
$isAdminChat = $isAdminRoute && function_exists('is_admin') && is_admin();
$chatMode = $isAdminChat ? 'admin' : 'customer';
$chatName = $isAdminChat ? 'MobiSaathi Admin' : 'MobiSaathi';
$chatLabel = $isAdminChat ? 'Owner operations assistant' : '24/7 shop support';
$welcome = $isAdminChat
    ? 'Assalam-u-Alaikum! Main MobiSaathi Admin hoon. Inventory, low stock, orders, sales aur dashboard tools mein help kar sakta hoon.'
    : 'Assalam-u-Alaikum! 👋 Main MobiSaathi hoon. Products, prices, stock aur shop services ke baare mein pooch sakte hain.';
?>
<div class="grok-chat <?= $isAdminChat ? 'grok-chat-admin' : 'grok-chat-customer' ?>" data-grok-chat data-mode="<?=e($chatMode)?>" data-endpoint="<?= e(url('api/grok.php')) ?>">
  <button class="grok-toggle" type="button" aria-label="Open <?=e($chatName)?>" aria-expanded="false">
    <img src="<?= url('assets/images/mobisaathi-bot-logo.png') ?>" alt="" width="58" height="58">
  </button>
  <section class="grok-panel" aria-label="<?=e($chatName)?> support">
    <header class="grok-head">
      <div class="grok-identity">
        <img src="<?= url('assets/images/mobisaathi-bot-logo.png') ?>" alt="" width="38" height="38">
        <div><strong><?=e($chatName)?></strong><small><?=e($chatLabel)?></small></div>
      </div>
      <button class="grok-close" type="button" aria-label="Close chat">×</button>
    </header>
    <div class="grok-messages"><div class="grok-msg bot"><?=e($welcome)?></div></div>
    <?php if ($isAdminChat): ?>
      <div class="grok-quick-replies" role="group" aria-label="Administrator help topics">
        <button type="button" data-prompt="Low stock items ka status batao">Low stock</button>
        <button type="button" data-prompt="Pending orders kitne hain?">Pending orders</button>
        <button type="button" data-prompt="Aaj ki sales batao">Today sales</button>
        <button type="button" data-prompt="Inventory adjustment kaise karni hai?">Adjust inventory</button>
      </div>
      <div class="grok-note">Private operations support. It can guide dashboard actions but cannot execute them without your confirmation.</div>
    <?php else: ?>
      <div class="grok-quick-replies" role="group" aria-label="Quick customer support questions">
        <button type="button" data-prompt="Shop location kya hai?">Shop location</button>
        <button type="button" data-prompt="Contact number kya hai?">Contact number</button>
        <button type="button" data-prompt="Aap kaun si services provide karte hain?">Services</button>
        <button type="button" data-prompt="EasyPaisa aur JazzCash service available hai?">EasyPaisa &amp; JazzCash</button>
      </div>
      <div class="grok-note">Prices and stock are taken from the shop database. Orders and payment are confirmed directly by the shop.</div>
    <?php endif; ?>
    <form class="grok-form">
      <input class="grok-input" type="text" maxlength="1200" autocomplete="off" placeholder="Message <?=e($chatName)?>…">
      <button class="grok-send" type="submit">Send</button>
    </form>
  </section>
</div>
<link rel="stylesheet" href="<?= url('assets/css/chat.css') ?>">
<script src="<?= url('assets/js-chat.js') ?>" defer></script>
