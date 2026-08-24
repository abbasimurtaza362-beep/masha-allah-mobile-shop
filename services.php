<?php
$page_title = 'Services | Masha Allah Mobile';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$services = db()->query(
    "SELECT s.*
     FROM services s
     INNER JOIN (
         SELECT name, MIN(id) AS id
         FROM services
         WHERE status = 'active'
           AND name IN (
               'Mobile Accessories',
               'Mobile Repairing',
               'Mobile Software Services',
               'EasyPaisa Services',
               'JazzCash Services'
           )
         GROUP BY name
     ) canonical ON canonical.id = s.id
     ORDER BY CASE s.name
         WHEN 'Mobile Accessories' THEN 1
         WHEN 'Mobile Repairing' THEN 2
         WHEN 'Mobile Software Services' THEN 3
         WHEN 'EasyPaisa Services' THEN 4
         WHEN 'JazzCash Services' THEN 5
         ELSE 6
     END"
)->fetchAll();
?>

<main>
    <section class="page-hero">
        <div class="container">
            <span class="eyebrow">Shop services</span>
            <h1>Practical mobile and digital support, close to home.</h1>
        </div>
    </section>

    <section class="section services-page-section">
        <div class="container grid three">
            <?php foreach ($services as $service): ?>
                <article class="card">
                    <?php if (!empty($service['image_path'])): ?>
                        <img class="product-img" loading="lazy" decoding="async" width="480" height="480" src="<?= url((string)$service['image_path']) ?>" alt="<?= e((string)$service['name']) ?> service image">
                    <?php else: ?>
                        <span class="service-icon">✦</span>
                    <?php endif; ?>
                    <h3><?= e((string)$service['name']) ?></h3>
                    <p><?= e((string)$service['description']) ?></p>
                    <a href="<?= url('contact.php#inquiry') ?>">Ask about this service →</a>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="container services-contact-wrap">
            <div class="notice">
                <h2>Need a quick answer before visiting?</h2>
                <p>Message the shop with your device model and what you need. The team can guide you on the next step without promising work that has not been assessed.</p>
                <a class="button gold" href="<?= url('contact.php#inquiry') ?>">Contact the shop</a>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
