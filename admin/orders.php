<?php
declare(strict_types=1);

$admin_title = 'Orders';
require __DIR__ . '/layout_top.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'create') {
            $customer = trim((string)($_POST['customer_name'] ?? ''));
            $phone = trim((string)($_POST['customer_phone'] ?? ''));
            $email = filter_var(trim((string)($_POST['customer_email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: null;
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            $paymentMethod = (string)($_POST['payment_method'] ?? 'cash');
            $paymentStatus = (string)($_POST['payment_status'] ?? 'unpaid');
            $note = trim((string)($_POST['note'] ?? ''));
            if (text_length($customer) < 2 || text_length($customer) > 100 || text_length($phone) < 5 || text_length($phone) > 50 || $productId < 1 || $quantity < 1 || text_length($note) > 3000 || !in_array($paymentMethod, ['cash','easypaisa','jazzcash','bank_transfer','other'], true) || !in_array($paymentStatus, ['unpaid','partial','paid','refunded'], true)) {
                throw new RuntimeException('Complete the order details correctly.');
            }
            $pdo->beginTransaction();
            $productStmt = $pdo->prepare("SELECT id,name,selling_price,quantity FROM products WHERE id=? AND status='active' FOR UPDATE");
            $productStmt->execute([$productId]);
            $product = $productStmt->fetch();
            if (!$product || (int)$product['quantity'] < $quantity) throw new RuntimeException('The selected product does not have enough current stock.');
            $total = round((float)$product['selling_price'] * $quantity, 2);
            $orderNumber = 'ORD-' . date('Ymd-His') . '-' . random_int(1000, 9999);
            $pdo->prepare('INSERT INTO orders(order_number,customer_name,customer_phone,customer_email,payment_status,payment_method,subtotal,total_amount,note,admin_user_id) VALUES(?,?,?,?,?,?,?,?,?,?)')
                ->execute([$orderNumber, $customer, $phone, $email, $paymentStatus, $paymentMethod, $total, $total, $note !== '' ? $note : null, (int)($_SESSION['user']['id'] ?? 0) ?: null]);
            $orderId = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,unit_price,quantity,line_total) VALUES(?,?,?,?,?,?)')
                ->execute([$orderId, $product['id'], $product['name'], $product['selling_price'], $quantity, $total]);
            $pdo->commit();
            flash('success', 'Order ' . $orderNumber . ' created. Complete it when the sale is fulfilled.');
        } elseif ($action === 'update') {
            $orderId = (int)($_POST['order_id'] ?? 0);
            $newStatus = (string)($_POST['status'] ?? 'new');
            $paymentStatus = (string)($_POST['payment_status'] ?? 'unpaid');
            if ($orderId < 1 || !in_array($newStatus, ['new','confirmed','processing','ready','completed','cancelled'], true) || !in_array($paymentStatus, ['unpaid','partial','paid','refunded'], true)) {
                throw new RuntimeException('Choose a valid order and payment status.');
            }
            $pdo->beginTransaction();
            $orderStmt = $pdo->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch();
            if (!$order) throw new RuntimeException('Order not found.');
            $itemsStmt = $pdo->prepare('SELECT oi.*,p.quantity current_quantity,p.name current_name FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? FOR UPDATE');
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll();

            if ($newStatus === 'completed' && !(int)$order['stock_applied']) {
                foreach ($items as $item) {
                    if (!$item['product_id'] || $item['current_quantity'] === null || (int)$item['current_quantity'] < (int)$item['quantity']) throw new RuntimeException('Current stock is insufficient to complete this order.');
                    $before = (int)$item['current_quantity']; $after = $before - (int)$item['quantity'];
                    $pdo->prepare('UPDATE products SET quantity=? WHERE id=?')->execute([$after, $item['product_id']]);
                    $pdo->prepare('INSERT INTO inventory_movements(product_id,product_name,quantity_before,quantity_change,quantity_after,movement_type,note,admin_user_id) VALUES(?,?,?,?,?,?,?,?)')->execute([$item['product_id'], $item['current_name'] ?: $item['product_name'], $before, -(int)$item['quantity'], $after, 'sales_order', 'Completed order ' . $order['order_number'], (int)($_SESSION['user']['id'] ?? 0) ?: null]);
                }
                $stockApplied = 1;
            } elseif ($newStatus === 'cancelled' && (int)$order['stock_applied']) {
                foreach ($items as $item) {
                    if (!$item['product_id'] || $item['current_quantity'] === null) continue;
                    $before = (int)$item['current_quantity']; $after = $before + (int)$item['quantity'];
                    $pdo->prepare('UPDATE products SET quantity=? WHERE id=?')->execute([$after, $item['product_id']]);
                    $pdo->prepare('INSERT INTO inventory_movements(product_id,product_name,quantity_before,quantity_change,quantity_after,movement_type,note,admin_user_id) VALUES(?,?,?,?,?,?,?,?)')->execute([$item['product_id'], $item['current_name'] ?: $item['product_name'], $before, (int)$item['quantity'], $after, 'order_cancelled_restock', 'Cancelled order ' . $order['order_number'], (int)($_SESSION['user']['id'] ?? 0) ?: null]);
                }
                $stockApplied = 0;
            } else {
                $stockApplied = (int)$order['stock_applied'];
            }
            $pdo->prepare('UPDATE orders SET status=?,payment_status=?,stock_applied=?,admin_user_id=? WHERE id=?')->execute([$newStatus, $paymentStatus, $stockApplied, (int)($_SESSION['user']['id'] ?? 0) ?: null, $orderId]);
            $pdo->commit();
            flash('success', 'Order ' . $order['order_number'] . ' updated.');
        } else {
            throw new RuntimeException('Invalid order action.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
    }
    redirect('admin/orders.php');
}

$products = $pdo->query("SELECT id,name,selling_price,quantity FROM products WHERE status='active' AND quantity>0 ORDER BY name")->fetchAll();
$orders = $pdo->query("SELECT o.*,GROUP_CONCAT(CONCAT(oi.product_name,' ×',oi.quantity) ORDER BY oi.id SEPARATOR ', ') items FROM orders o JOIN order_items oi ON oi.order_id=o.id GROUP BY o.id ORDER BY o.created_at DESC LIMIT 80")->fetchAll();
?>
<div class="orders-layout">
    <section class="admin-panel order-create-panel"><div class="admin-panel-head"><div><span class="eyebrow" style="color:#ae7d2c">New sale</span><h3>Create order</h3></div></div>
        <form class="order-form" method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="create">
            <label>Customer name<input required maxlength="100" name="customer_name" placeholder="Customer name"></label>
            <label>Phone number<input required maxlength="50" name="customer_phone" placeholder="03xx xxxxxxx"></label>
            <label>Email <span class="muted">(optional)</span><input type="email" maxlength="190" name="customer_email" placeholder="customer@example.com"></label>
            <label>Product<select required name="product_id"><option value="">Choose an in-stock product</option><?php foreach($products as $product):?><option value="<?=$product['id']?>"><?=e($product['name'])?> — PKR <?=number_format((float)$product['selling_price'])?> (<?=number_format((int)$product['quantity'])?> available)</option><?php endforeach;?></select></label>
            <label>Quantity<input required min="1" type="number" name="quantity" value="1"></label>
            <label>Payment method<select name="payment_method"><option value="cash">Cash</option><option value="easypaisa">EasyPaisa</option><option value="jazzcash">JazzCash</option><option value="bank_transfer">Bank transfer</option><option value="other">Other</option></select></label>
            <label>Payment status<select name="payment_status"><option value="unpaid">Unpaid</option><option value="partial">Partial</option><option value="paid">Paid</option></select></label>
            <label class="full">Order note<textarea name="note" maxlength="3000" placeholder="Optional delivery, device or payment note"></textarea></label>
            <button class="button dark full" type="submit">Create order</button>
        </form>
    </section>
    <section class="admin-panel"><div class="admin-panel-head"><div><span class="eyebrow" style="color:#ae7d2c">Workflow</span><h3>Order management</h3></div><span class="muted"><?=count($orders)?> recent orders</span></div>
        <div class="table-wrap"><table class="admin-table orders-table"><thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Fulfilment</th><th>Payment</th><th>Save</th></tr></thead><tbody><?php foreach($orders as $order):?><tr><td><strong><?=e($order['order_number'])?></strong><br><span class="muted"><?=e(date('d M, H:i',strtotime($order['created_at'])))?></span></td><td><?=e($order['customer_name'])?><br><span class="muted"><?=e($order['customer_phone'])?></span></td><td><?=e($order['items'])?></td><td>PKR <?=number_format((float)$order['total_amount'])?></td><td colspan="3"><form class="order-status-form" method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="update"><input type="hidden" name="order_id" value="<?=$order['id']?>"><select name="status"><option value="new" <?=$order['status']==='new'?'selected':''?>>New</option><option value="confirmed" <?=$order['status']==='confirmed'?'selected':''?>>Confirmed</option><option value="processing" <?=$order['status']==='processing'?'selected':''?>>Processing</option><option value="ready" <?=$order['status']==='ready'?'selected':''?>>Ready</option><option value="completed" <?=$order['status']==='completed'?'selected':''?>>Completed</option><option value="cancelled" <?=$order['status']==='cancelled'?'selected':''?>>Cancelled</option></select><select name="payment_status"><option value="unpaid" <?=$order['payment_status']==='unpaid'?'selected':''?>>Unpaid</option><option value="partial" <?=$order['payment_status']==='partial'?'selected':''?>>Partial</option><option value="paid" <?=$order['payment_status']==='paid'?'selected':''?>>Paid</option><option value="refunded" <?=$order['payment_status']==='refunded'?'selected':''?>>Refunded</option></select><button class="button small dark" type="submit">Update</button></form></td></tr><?php endforeach;?><?php if(!$orders):?><tr><td colspan="7" class="muted">No orders have been created yet.</td></tr><?php endif;?></tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/layout_bottom.php'; ?>
