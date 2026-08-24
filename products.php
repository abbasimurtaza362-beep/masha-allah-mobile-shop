<?php
declare(strict_types=1);

$page_title = 'Products | Masha Allah Mobile';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$q = trim((string)($_GET['q'] ?? ''));
$category = max(0, (int)($_GET['category'] ?? 0));
$sort = (string)($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$allowedSorts = [
    'newest' => 'p.id DESC',
    'low' => 'p.selling_price ASC, p.id DESC',
    'high' => 'p.selling_price DESC, p.id DESC',
    'name' => 'p.name ASC, p.id DESC',
];
$order = $allowedSorts[$sort] ?? $allowedSorts['newest'];
$sort = array_search($order, $allowedSorts, true) ?: 'newest';

// LEFT JOIN keeps All Categories complete even if an older record has a
// missing/invalid category relation. A valid category filter still uses the
// product's category_id directly.
$base = " FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.status = 'active'";
$params = [];

if ($q !== '') {
    $base .= ' AND p.name LIKE ?';
    $params[] = '%' . $q . '%';
}

if ($category > 0) {
    $base .= ' AND p.category_id = ?';
    $params[] = $category;
}

$countStmt = db()->prepare('SELECT COUNT(p.id)' . $base);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    "SELECT p.*, COALESCE(c.name, 'Other') AS category" .
    $base .
    " ORDER BY $order LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = db()->query(
    "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name"
)->fetchAll();

$selectedCategoryName = '';
foreach ($categories as $cat) {
    if ($category === (int)$cat['id']) {
        $selectedCategoryName = (string)$cat['name'];
        break;
    }
}

$queryBase = [
    'q' => $q,
    'category' => $category > 0 ? $category : null,
    'sort' => $sort,
];
$pageUrl = static function (int $target) use ($queryBase): string {
    $data = array_filter(
        array_merge($queryBase, ['page' => $target]),
        static fn($value): bool => $value !== null && $value !== ''
    );
    return 'products.php?' . http_build_query($data);
};

$hasFilters = $q !== '' || $category > 0;
$productNote = static function (string $category): string {
    return match (strtolower(trim($category))) {
        'cables' => 'Reliable daily charging and data-use choice.',
        'chargers' => 'Useful for regular home or office charging.',
        'earphones' => 'Convenient for calls and everyday listening.',
        'speakers' => 'A practical pick for portable audio.',
        'tws' => 'A convenient wireless option for daily use.',
        'batteries' => 'A practical replacement option; confirm model compatibility.',
        default => 'A practical recommendation for everyday mobile use.'
    };
};
?>

<main>
    <section class="page-hero">
        <div class="container">
            <span class="eyebrow">The catalogue</span>
            <h1>Shop accessories with clear starting prices.</h1>
        </div>
    </section>

    <section class="section products-page-section">
        <div class="container">
            <form class="filters" method="get" action="products.php">
                <input name="q" value="<?= e($q) ?>" placeholder="Search products">
                <select name="category" aria-label="Filter products by category">
                    <option value="0" <?= $category === 0 ? 'selected' : '' ?>>All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $category === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= e((string)$c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="sort" aria-label="Sort products">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest products</option>
                    <option value="low" <?= $sort === 'low' ? 'selected' : '' ?>>Price: low to high</option>
                    <option value="high" <?= $sort === 'high' ? 'selected' : '' ?>>Price: high to low</option>
                    <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A–Z</option>
                </select>
                <button class="button dark" type="submit">Filter</button>
            </form>

            <div class="section-head">
                <h2>
                    <?= $total ?>
                    <?= $selectedCategoryName !== '' ? e($selectedCategoryName) . ' products' : 'products' ?>
                </h2>
                <span class="muted">
                    <?php if ($total > 0): ?>
                        Page <?= $page ?> of <?= $pages ?>
                    <?php else: ?>
                        No products found
                    <?php endif; ?>
                </span>
            </div>

            <?php
            $renderPagination = static function () use ($page, $pages, $pageUrl): void {
                $previousUrl = $pageUrl(max(1, $page - 1));
                $nextUrl = $pageUrl(min($pages, $page + 1));
                ?>
                <nav class="pagination products-pagination" aria-label="Product pages">
                    <?php if ($page > 1): ?>
                        <a class="button outline-dark" href="<?= url($previousUrl) ?>" rel="prev">← Previous</a>
                    <?php else: ?>
                        <span class="button outline-dark pagination-disabled" aria-disabled="true">← Previous</span>
                    <?php endif; ?>

                    <div class="pagination-pages" aria-label="Page numbers">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="page-number active" aria-current="page"><?= $i ?></span>
                            <?php else: ?>
                                <a class="page-number" href="<?= url($pageUrl($i)) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>

                    <?php if ($page < $pages): ?>
                        <a class="button dark" href="<?= url($nextUrl) ?>" rel="next">Next →</a>
                    <?php else: ?>
                        <span class="button dark pagination-disabled" aria-disabled="true">Next →</span>
                    <?php endif; ?>
                </nav>
                <?php
            };

            ?>

            <?php if (!$products): ?>
                <div class="empty-state">
                    <span class="empty-state-icon">⌕</span>
                    <div>
                        <h3>No products found</h3>
                        <p>Try different filters.</p>
                        <?php if ($hasFilters): ?>
                            <a class="button outline-dark" href="<?= url('products.php') ?>">Clear filters</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid four">
                    <?php foreach ($products as $product): ?>
                        <article class="card product" data-product-card data-product-id="<?= (int)$product['id'] ?>" data-product-name="<?= e((string)$product['name']) ?>">
                            <?php if (!empty($product['image_path'])): ?>
                                <img src="<?= url((string)$product['image_path']) ?>" alt="<?= e((string)$product['name']) ?>" loading="lazy" width="960" height="960">
                            <?php else: ?>
                                <div class="product-image image-placeholder">⌁</div>
                            <?php endif; ?>
                            <div class="product-body">
                                <span class="badge"><?= e((string)$product['category']) ?></span>
                                <h3><?= e((string)$product['name']) ?></h3>
                                <span class="badge <?= stock_class((int)$product['quantity']) ?>"><?= stock_label((int)$product['quantity']) ?></span>
                                <p class="price">PKR <?= number_format((float)$product['selling_price']) ?></p>
                                <p class="product-review"><span aria-hidden="true">★</span> <?= e($productNote((string)$product['category'])) ?></p>
                                <div class="product-actions"><a href="<?= url('contact.php#inquiry') ?>">Ask shop →</a><button class="product-chat-link" type="button" data-product-select>Ask chatbot</button></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="products-pagination-bottom">
                <?php $renderPagination(); ?>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
