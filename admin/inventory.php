<?php
declare(strict_types=1);

$admin_title = 'Inventory Control';
require __DIR__ . '/layout_top.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $mode = (string)($_POST['mode'] ?? 'restock');
    $amount = (int)($_POST['amount'] ?? 0);
    $note = trim((string)($_POST['note'] ?? ''));

    try {
        if ($productId < 1 || !in_array($mode, ['restock', 'reduce', 'set'], true) || $amount < 0 || text_length($note) > 255) {
            throw new RuntimeException('Enter a valid inventory adjustment.');
        }

        $pdo->beginTransaction();
        $productStmt = $pdo->prepare('SELECT id,name,quantity FROM products WHERE id=? FOR UPDATE');
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch();
        if (!$product) throw new RuntimeException('The selected product is no longer available.');

        $before = (int)$product['quantity'];
        $after = match ($mode) {
            'restock' => $before + $amount,
            'reduce' => $before - $amount,
            'set' => $amount,
        };
        if ($after < 0) throw new RuntimeException('Stock cannot be reduced below zero.');
        $change = $after - $before;
        if ($change === 0) throw new RuntimeException('This adjustment would not change the stock level.');

        $pdo->prepare('UPDATE products SET quantity=? WHERE id=?')->execute([$after, $productId]);
        $movementType = $mode === 'restock' ? 'restock' : ($mode === 'reduce' ? 'stock_reduction' : 'stock_set');
        $movementNote = $note !== '' ? $note : 'Administrator inventory adjustment';
        $pdo->prepare('INSERT INTO inventory_movements(product_id,product_name,quantity_before,quantity_change,quantity_after,movement_type,note,admin_user_id) VALUES(?,?,?,?,?,?,?,?)')
            ->execute([$productId, $product['name'], $before, $change, $after, $movementType, $movementNote, (int)($_SESSION['user']['id'] ?? 0) ?: null]);
        $pdo->commit();
        flash('success', 'Inventory updated for ' . $product['name'] . '.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
    }
    redirect('admin/inventory.php');
}

$q = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? 'all');
$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = '(p.name LIKE ? OR c.name LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($status === 'out') $conditions[] = 'p.quantity = 0';
if ($status === 'reorder') $conditions[] = 'p.quantity > 0 AND p.quantity <= p.reorder_level';
if ($status === 'healthy') $conditions[] = 'p.quantity > p.reorder_level';
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

$productsStmt = $pdo->prepare('SELECT p.*, c.name category FROM products p JOIN categories c ON c.id=p.category_id' . $where . ' ORDER BY p.quantity ASC,p.name ASC');
$productsStmt->execute($params);
$products = $productsStmt->fetchAll();

$stockValue = (float)$pdo->query('SELECT COALESCE(SUM(quantity * selling_price),0) FROM products')->fetchColumn();
$reorderCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE quantity BETWEEN 1 AND reorder_level')->fetchColumn();
$outCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE quantity=0')->fetchColumn();
$recent = $pdo->query('SELECT m.*, COALESCE(p.name,m.product_name) product_name, u.name admin_name FROM inventory_movements m LEFT JOIN products p ON p.id=m.product_id LEFT JOIN users u ON u.id=m.admin_user_id ORDER BY m.created_at DESC LIMIT 8')->fetchAll();
?>
<div class="inventory-kpis">
    <article><span>Inventory value</span><strong>PKR <?=number_format($stockValue)?></strong></article>
    <article><span>Needs reorder</span><strong><?=number_format($reorderCount)?></strong></article>
    <article><span>Out of stock</span><strong><?=number_format($outCount)?></strong></article>
</div>

<section class="admin-panel">
    <div class="admin-panel-head"><div><span class="eyebrow" style="color:#ae7d2c">Live stock</span><h3>Inventory control</h3></div><span class="muted"><?=count($products)?> products shown</span></div>
    <form class="inventory-filters" method="get">
        <input name="q" value="<?=e($q)?>" placeholder="Search product or category">
        <select name="status"><option value="all">All stock states</option><option value="out" <?=$status==='out'?'selected':''?>>Out of stock</option><option value="reorder" <?=$status==='reorder'?'selected':''?>>Needs reorder</option><option value="healthy" <?=$status==='healthy'?'selected':''?>>Healthy stock</option></select>
        <button class="button dark" type="submit">Apply view</button>
        <?php if ($q !== '' || $status !== 'all'): ?><a class="button outline-dark" href="<?=url('admin/inventory.php')?>">Clear</a><?php endif; ?>
    </form>
    <div class="table-wrap"><table class="admin-table inventory-table"><thead><tr><th>Product</th><th>Category</th><th>Current stock</th><th>Reorder at</th><th>Stock state</th><th>Quick adjustment</th></tr></thead><tbody>
        <?php foreach ($products as $product): $quantity=(int)$product['quantity']; $reorder=(int)$product['reorder_level']; $state=$quantity===0?'Out of stock':($quantity<=$reorder?'Needs reorder':'Healthy'); ?>
            <tr>
                <td><strong><?=e($product['name'])?></strong><br><span class="muted">PKR <?=number_format((float)$product['selling_price'])?></span></td>
                <td><?=e($product['category'])?></td>
                <td><strong><?=number_format($quantity)?></strong></td>
                <td><?=number_format($reorder)?></td>
                <td><span class="inventory-state <?= $quantity===0?'out':($quantity<=$reorder?'reorder':'healthy')?>"><?=e($state)?></span></td>
                <td>
                    <form class="inventory-adjust" method="post">
                        <input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="product_id" value="<?=$product['id']?>">
                        <select name="mode" aria-label="Adjustment type"><option value="restock">Add</option><option value="reduce">Reduce</option><option value="set">Set to</option></select>
                        <input name="amount" min="0" required type="number" value="1" aria-label="Quantity">
                        <input name="note" maxlength="255" placeholder="Reason (optional)" aria-label="Adjustment reason">
                        <button class="button small dark" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?><tr><td colspan="6" class="muted">No inventory records match this view.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>

<section class="admin-panel" style="margin-top:18px">
    <div class="admin-panel-head"><div><span class="eyebrow" style="color:#ae7d2c">Audit trail</span><h3>Recent stock activity</h3></div><span class="muted">Last <?=count($recent)?> adjustments</span></div>
    <div class="table-wrap"><table class="admin-table"><thead><tr><th>Product</th><th>Change</th><th>Stock</th><th>Type</th><th>Note</th><th>When</th></tr></thead><tbody>
        <?php foreach ($recent as $movement): ?><tr><td><strong><?=e($movement['product_name'])?></strong></td><td class="<?= (int)$movement['quantity_change'] > 0 ? 'movement-up' : 'movement-down' ?>"><?= (int)$movement['quantity_change'] > 0 ? '+' : '' ?><?=number_format((int)$movement['quantity_change'])?></td><td><?=number_format((int)$movement['quantity_before'])?> → <?=number_format((int)$movement['quantity_after'])?></td><td><?=e(ucwords(str_replace('_',' ',(string)$movement['movement_type'])))?></td><td><?=e((string)($movement['note'] ?? '—'))?></td><td><?=e(date('d M, H:i',strtotime((string)$movement['created_at'])))?></td></tr><?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="6" class="muted">No stock movements have been recorded yet.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/layout_bottom.php'; ?>
