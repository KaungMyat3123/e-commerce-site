<?php
// invoice.php — order confirmation / voucher (printable)
require_once __DIR__ . '/includes/db.php';

$orderId = (int) ($_GET['order'] ?? 0);

$stmt = $conn->prepare(
    'SELECT o.*, pm.method_name
     FROM Orders o LEFT JOIN PaymentMethods pm ON pm.payment_method_id = o.payment_method_id
     WHERE o.order_id = ?'
);
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    $pageTitle = 'Invoice Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container py-5"><div class="alert alert-warning">Invoice not found. <a href="shop.php" class="alert-link">Back to shop</a>.</div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$itemsStmt = $conn->prepare(
    'SELECT oi.quantity, oi.unit_price, p.name, p.product_id
     FROM OrderItems oi LEFT JOIN Products p ON p.product_id = oi.product_id
     WHERE oi.order_id = ?'
);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$pageTitle = 'Invoice #' . $orderId;
$activePage = '';
require __DIR__ . '/includes/header.php';
?>

<section class="container py-5" id="invoice">
    <?php if (empty($_GET['print'])): ?>
        <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-check-circle-fill me-2"></i>Thank you, <?= e($order['customer_name']) ?>! Your order <strong>#<?= (int) $order['order_id'] ?></strong> has been placed and is pending confirmation.</span>
            <a href="shop.php" class="btn btn-success btn-sm">Continue Shopping</a>
        </div>
    <?php endif; ?>

    <div class="card shadow invoice-card mx-auto">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h4 class="fw-bold mb-1"><i class="bi bi-bag-fill me-1"></i><?= e($config['app_name']) ?></h4>
                    <p class="text-muted small mb-0">No. 45, Yangon, Myanmar<br>+95 9 123 456 789 &middot; support@example.com</p>
                </div>
                <div class="text-md-end">
                    <h5 class="mb-0">INVOICE / VOUCHER</h5>
                    <p class="mb-0">#<?= str_pad((string) $order['order_id'], 6, '0', STR_PAD_LEFT) ?></p>
                    <p class="small mb-0">Date: <?= e(date('d M Y, h:i A', strtotime($order['created_at']))) ?></p>
                    <span class="badge bg-<?= $order['order_status'] === 'Cancelled' ? 'secondary' : ($order['order_status'] === 'Shipped' ? 'primary' : 'success') ?>"><?= e($order['order_status']) ?></span>
                </div>
            </div>

            <div class="row gy-3 mb-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-muted small text-uppercase">Billed To</h6>
                    <strong><?= e($order['customer_name']) ?></strong><br>
                    <span class="small"><?= e($order['customer_email']) ?> &middot; <?= e($order['customer_phone']) ?></span><br>
                    <span class="small text-muted"><?= nl2br(e($order['shipping_address'])) ?></span>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="fw-bold text-muted small text-uppercase d-md-none d-block">Payment</h6>
                    <p class="mb-0"><strong>Payment Method:</strong> <?= e($order['method_name'] ?? 'N/A') ?></p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($item['name'] ?? 'Deleted product') ?></td>
                                <td class="text-center"><?= (int) $item['quantity'] ?></td>
                                <td class="text-end"><?= currency($item['unit_price']) ?></td>
                                <td class="text-end"><?= currency((float) $item['unit_price'] * (int) $item['quantity']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Subtotal</th>
                            <th class="text-end"><?= currency($order['total_amount']) ?></th>
                        </tr>
                        <tr>
                            <th colspan="4" class="text-end">Shipping</th>
                            <th class="text-end">Free</th>
                        </tr>
                        <tr class="table-primary">
                            <th colspan="4" class="text-end fs-5">Total</th>
                            <th class="text-end fs-5"><?= currency($order['total_amount']) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="small text-muted mt-4 mb-0">Thank you for shopping with us! Keep this invoice as proof of purchase. Goods may be exchanged within 7 days with the original voucher.</p>
        </div>
    </div>

    <?php if (empty($_GET['print'])): ?>
        <div class="text-center mt-4 no-print">
            <button class="btn btn-outline-primary me-2" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print / Save as PDF</button>
            <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
