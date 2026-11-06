<?php

// admin/order-detail.php — order detail, itemized summary & status updates

require_once __DIR__ . '/../includes/db.php';

require_once __DIR__ . '/../includes/auth.php';

require_admin();



$STATUSES = ['Pending', 'Confirmed', 'Shipped', 'Cancelled'];



$id = (int) ($_GET['id'] ?? $_POST['order_id'] ?? 0);



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    $newStatus = $_POST['order_status'] ?? '';

    if (in_array($newStatus, $STATUSES, true)) {

        $stmt = $conn->prepare('UPDATE Orders SET order_status = ? WHERE order_id = ?');

        $stmt->execute([$newStatus, $id]);

        flash('success', "Order #$id status updated to $newStatus.");

    } else {

        flash('error', 'Invalid status.');

    }

    header('Location: order-detail.php?id=' . $id);

    exit;

}



$stmt = $conn->prepare(

    'SELECT o.*, pm.method_name FROM Orders o LEFT JOIN PaymentMethods pm ON pm.payment_method_id = o.payment_method_id WHERE o.order_id = ?'

);

$stmt->execute([$id]);

$order = $stmt->fetch();



if (!$order) {

    flash('error', 'Order not found.');

    header('Location: orders.php');

    exit;

}



$itemsStmt = $conn->prepare(

    'SELECT oi.quantity, oi.unit_price, p.name, p.product_id

     FROM OrderItems oi LEFT JOIN Products p ON p.product_id = oi.product_id

     WHERE oi.order_id = ?'

);

$itemsStmt->execute([$id]);

$items = $itemsStmt->fetchAll();

$computedTotal = array_sum(array_map(fn ($i) => (float) $i['unit_price'] * (int) $i['quantity'], $items));



$badgeMap = ['Pending' => 'warning', 'Confirmed' => 'success', 'Shipped' => 'primary', 'Cancelled' => 'secondary'];



$adminTitle = 'Order #' . $id;

$adminActive = 'orders';

require __DIR__ . '/../includes/admin-header.php';

?>



<div class="d-flex justify-content-between align-items-center mb-3">

    <a href="orders.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Orders</a>

    <a href="../invoice.php?order=<?= (int) $order['order_id'] ?>" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-printer me-1"></i>View Invoice</a>

</div>



<div class="row g-4">

    <div class="col-lg-4">

        <div class="card mb-4">

            <div class="card-header fw-bold">Customer</div>

            <div class="card-body">

                <p class="mb-1 fw-semibold"><?= e($order['customer_name']) ?></p>

                <p class="small mb-1"><i class="bi bi-envelope me-1"></i><?= e($order['customer_email']) ?></p>

                <p class="small mb-3"><i class="bi bi-telephone me-1"></i><?= e($order['customer_phone']) ?></p>

                <h6 class="fw-bold small text-muted text-uppercase">Shipping Address</h6>

                <p class="small mb-0"><?= nl2br(e($order['shipping_address'])) ?></p>

            </div>

        </div>



        <div class="card mb-4">

            <div class="card-header fw-bold">Payment</div>

            <div class="card-body">

                <p class="mb-1 small"><strong>Method:</strong> <?= e($order['method_name'] ?? 'N/A') ?></p>

                <p class="mb-0 small"><strong>Placed:</strong> <?= e(date('d M Y, h:i A', strtotime($order['created_at']))) ?></p>

            </div>

        </div>



        <div class="card">

            <div class="card-header fw-bold">Update Status</div>

            <div class="card-body">

                <p class="small mb-2">Current: <span class="badge bg-<?= $badgeMap[$order['order_status']] ?? 'secondary' ?>"><?= e($order['order_status']) ?></span></p>

                <form method="post" class="d-flex gap-2">

                    <?= csrf_field() ?>

                    <input type="hidden" name="order_id" value="<?= (int) $order['order_id'] ?>">

                    <select name="order_status" class="form-select form-select-sm">

                        <?php foreach ($STATUSES as $s): ?>

                            <option value="<?= $s ?>" <?= $order['order_status'] === $s ? 'selected' : '' ?>><?= $s ?></option>

                        <?php endforeach; ?>

                    </select>

                    <button class="btn btn-sm btn-primary">Save</button>

                </form>

            </div>

        </div>

    </div>



    <div class="col-lg-8">

        <div class="card">

            <div class="card-header fw-bold">Order Items</div>

            <div class="table-responsive">

                <table class="table mb-0 align-middle">

                    <thead class="table-light"><tr><th style="width:50px">#</th><th>Product</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Amount</th></tr></thead>

                    <tbody>

                        <?php foreach ($items as $i => $item): ?>

                            <tr>

                                <td><?= $i + 1 ?></td>

                                <td>

                                    <?php if ($item['product_id']): ?>

                                        <a href="products.php?action=edit&id=<?= (int) $item['product_id'] ?>"><?= e($item['name']) ?></a>

                                    <?php else: ?>

                                        <span class="text-muted"><?= e($item['name'] ?? 'Product removed from catalog') ?></span>

                                    <?php endif; ?>

                                </td>

                                <td class="text-center"><?= (int) $item['quantity'] ?></td>

                                <td class="text-end"><?= currency($item['unit_price']) ?></td>

                                <td class="text-end"><?= currency((float) $item['unit_price'] * (int) $item['quantity']) ?></td>

                            </tr>

                        <?php endforeach; ?>

                        <?php if (!$items): ?><tr><td colspan="5" class="text-center text-muted py-4">No items on this order.</td></tr><?php endif; ?>

                    </tbody>

                    <tfoot>

                        <tr class="table-primary">

                            <th colspan="4" class="text-end">Order Total</th>

                            <th class="text-end fw-bold fs-5"><?= currency($order['total_amount']) ?></th>

                        </tr>

                        <?php if (abs($computedTotal - (float) $order['total_amount']) > 0.005): ?>

                            <tr><td colspan="5" class="text-end small text-danger">Note: itemized sum (<?= currency($computedTotal) ?>) differs from the recorded total — a product was likely edited after purchase.</td></tr>

                        <?php endif; ?>

                    </tfoot>

                </table>

            </div>

        </div>

    </div>

</div>



<?php require __DIR__ . '/../includes/admin-footer.php'; ?>