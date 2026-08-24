<?php
declare(strict_types=1);

$admin_title = 'Manage Products';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_guard.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->beginTransaction();
            $productStmt = $pdo->prepare('SELECT id,name,quantity FROM products WHERE id=? FOR UPDATE');
            $productStmt->execute([$id]);
            $product = $productStmt->fetch();
            if (!$product) throw new RuntimeException('Product not found.');
            $pdo->prepare('INSERT INTO inventory_movements(product_id,product_name,quantity_before,quantity_change,quantity_after,movement_type,note,admin_user_id) VALUES(?,?,?,?,?,?,?,?)')->execute([$product['id'], $product['name'], (int)$product['quantity'], -(int)$product['quantity'], 0, 'product_deleted', 'Product removed from catalogue', (int)($_SESSION['user']['id'] ?? 0) ?: null]);
            $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
            $pdo->commit();
            flash('success', 'Product deleted.');
            redirect('admin/products.php');
        }

        if (!in_array($action, ['create', 'update'], true)) throw new RuntimeException('Invalid product action.');
        $name = trim((string)($_POST['name'] ?? ''));
        $category = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['selling_price'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $reorderLevel = (int)($_POST['reorder_level'] ?? 5);
        $description = trim((string)($_POST['description'] ?? ''));
        $status = ($_POST['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
        if (text_length($name) < 2 || !$category || $price < 0 || $quantity < 0 || $reorderLevel < 0 || text_length($description) < 4) throw new RuntimeException('Please complete all product fields correctly.');

        $newImage = upload_image('image');
        $pdo->beginTransaction();
        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $currentStmt = $pdo->prepare('SELECT id,name,quantity FROM products WHERE id=? FOR UPDATE');
            $currentStmt->execute([$id]);
            $current = $currentStmt->fetch();
            if (!$current) throw new RuntimeException('Product not found.');
            $sql = 'UPDATE products SET category_id=?,name=?,selling_price=?,quantity=?,reorder_level=?,description=?,status=?' . ($newImage ? ',image_path=?' : '') . ' WHERE id=?';
            $params = [$category, $name, $price, $quantity, $reorderLevel, $description, $status];
            if ($newImage) $params[] = $newImage;
            $params[] = $id;
            $pdo->prepare($sql)->execute($params);
            $change = $quantity - (int)$current['quantity'];
            if ($change !== 0) $pdo->prepare('INSERT INTO inventory_movements(product_id,product_name,quantity_before,quantity_change,quantity_after,movement_type,note,admin_user_id) VALUES(?,?,?,?,?,?,?,?)')->execute([$id, $name, (int)$current['quantity'], $change, $quantity, 'product_edit', 'Stock updated from product editor', (int)($_SESSION['user']['id'] ?? 0) ?: null]);
            $message = 'Product updated.';
        } else {
            $pdo->prepare('INSERT INTO products(category_id,name,selling_price,quantity,reorder_level,image_path,description,status) VALUES(?,?,?,?,?,?,?,?)')->execute([$category, $name, $price, $quantity, $reorderLevel, $newImage, $description, $status]);
            $id = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO inventory_movements(product_id,product_name,quantity_before,quantity_change,quantity_after,movement_type,note,admin_user_id) VALUES(?,?,?,?,?,?,?,?)')->execute([$id, $name, 0, $quantity, $quantity, 'product_created', 'Initial stock recorded from product editor', (int)($_SESSION['user']['id'] ?? 0) ?: null]);
            $message = 'Product added.';
        }
        $pdo->commit();
        flash('success', $message);
        redirect('admin/products.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
        redirect('admin/products.php');
    }
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$productSearch = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? 'all');
$filters = [];
$params = [];
if ($productSearch !== '') { $filters[] = '(p.name LIKE ? OR c.name LIKE ?)'; $params[] = '%' . $productSearch . '%'; $params[] = '%' . $productSearch . '%'; }
if (in_array($statusFilter, ['active', 'inactive'], true)) { $filters[] = 'p.status=?'; $params[] = $statusFilter; }
$where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';
$productsStmt = $pdo->prepare('SELECT p.*,c.name AS category FROM products p JOIN categories c ON c.id=p.category_id' . $where . ' ORDER BY p.id DESC');
$productsStmt->execute($params);
$products = $productsStmt->fetchAll();
require __DIR__ . '/layout_top.php';
?>
<span class="eyebrow" style="color:#ae7d2c">Inventory</span><h1>Manage products</h1>
<div class="admin-products-layout">
    <form class="form-card product-create-card" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="create">
        <span class="eyebrow" style="color:#ae7d2c">New item</span><h3>Add product</h3>
        <div class="form-grid product-create-grid">
            <label>Product name<input required name="name"></label>
            <label>Category<select required name="category_id"><option value="">Choose category</option><?php foreach($categories as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select></label>
            <label>Selling price (PKR)<input required min="0" step="0.01" type="number" name="selling_price"></label>
            <label>Quantity<input required min="0" type="number" name="quantity" value="0"></label>
            <label>Reorder level<input required min="0" type="number" name="reorder_level" value="5"><small class="muted">Alert at or below this stock.</small></label>
            <label>Image (JPG, PNG, WEBP; max 4 MB)<input type="file" accept=".jpg,.jpeg,.png,.webp" name="image"></label>
            <label>Description<textarea required name="description"></textarea></label>
            <label>Status<select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            <button class="button dark">Add product</button>
        </div>
    </form>

    <section class="product-list-panel admin-product-browser">
        <div class="product-list-head"><div><span class="eyebrow" style="color:#ae7d2c">Existing catalogue</span><h3>Product cards</h3><p class="muted">Products appear left to right. Select Edit on any card to open its saveable edit window.</p></div><span class="muted"><?=count($products)?> products shown</span></div>
        <form class="inventory-filters product-list-filters" method="get"><input name="q" value="<?=e($productSearch)?>" placeholder="Search products or categories"><select name="status"><option value="all">All statuses</option><option value="active" <?=$statusFilter==='active'?'selected':''?>>Active</option><option value="inactive" <?=$statusFilter==='inactive'?'selected':''?>>Inactive</option></select><button class="button dark" type="submit">Find product</button><?php if($productSearch!==''||$statusFilter!=='all'):?><a class="button outline-dark" href="<?=url('admin/products.php')?>">Clear</a><?php endif;?></form>
        <div class="admin-product-cd-grid">
            <?php foreach($products as $p): ?>
                <article class="admin-product-cd-card">
                    <?php if($p['image_path']): ?><img src="<?=url($p['image_path'])?>" alt="<?=e($p['name'])?>" loading="lazy"><?php else: ?><div class="admin-product-image-placeholder">⌁</div><?php endif; ?>
                    <div class="admin-product-cd-body"><span class="admin-product-category"><?=e($p['category'])?></span><h4 title="<?=e($p['name'])?>"><?=e($p['name'])?></h4><div class="admin-product-detail">PKR <?=number_format((float)$p['selling_price'])?></div><div class="admin-product-stock"><span class="inventory-state <?=$p['status']==='active'?'healthy':'out'?>"><?=e(ucfirst($p['status']))?></span><small><?=$p['quantity']?> in stock</small></div><button class="button small dark" type="button" data-edit-product data-product-id="<?=$p['id']?>" data-product-name="<?=e($p['name'])?>" data-product-category="<?=$p['category_id']?>" data-product-price="<?=e((string)$p['selling_price'])?>" data-product-quantity="<?=$p['quantity']?>" data-product-reorder="<?=$p['reorder_level']?>" data-product-description="<?=e($p['description'])?>" data-product-status="<?=e($p['status'])?>">Edit</button></div>
                </article>
            <?php endforeach; ?>
            <?php if(!$products): ?><div class="admin-product-grid-empty">No products match this view.</div><?php endif; ?>
        </div>
    </section>
</div>

<dialog class="product-edit-modal" data-product-edit-modal aria-labelledby="product-edit-title"><div class="modal-head"><div><span class="eyebrow" style="color:#ae7d2c">Existing product</span><h2 id="product-edit-title">Edit product</h2><p class="muted" data-modal-product-label></p></div><button class="modal-close" type="button" data-close-product-modal aria-label="Close editor">×</button></div><form class="modal-product-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" data-modal-field="id"><label>Product name<input required name="name" data-modal-field="name"></label><label>Category<select required name="category_id" data-modal-field="category"><?php foreach($categories as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select></label><label>Selling price (PKR)<input required min="0" step="0.01" type="number" name="selling_price" data-modal-field="price"></label><label>Quantity<input required min="0" type="number" name="quantity" data-modal-field="quantity"></label><label>Reorder level<input required min="0" type="number" name="reorder_level" data-modal-field="reorder"></label><label class="full-modal">Description<textarea required name="description" data-modal-field="description"></textarea></label><label>Status<select name="status" data-modal-field="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></label><label>New image <small class="muted">Optional — keep current image if blank.</small><input type="file" accept=".jpg,.jpeg,.png,.webp" name="image"></label><div class="modal-actions"><button class="button outline-dark" type="button" data-close-product-modal>Cancel</button><button class="button dark" type="submit">Save changes</button></div></form></dialog>
<?php require __DIR__ . '/layout_bottom.php'; ?>
