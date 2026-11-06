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

// ---- Reviews: purchase rating on the invoice ----
$orderProductIds = array_values(array_unique(array_filter(array_column($items, 'product_id'))));

$reviewErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_review') {
    csrf_verify();
    $productId    = (int) ($_POST['product_id'] ?? 0);
    $reviewerName = trim($_POST['reviewer_name'] ?? '');
    $comment      = trim($_POST['comment'] ?? '');

    if (!in_array($productId, $orderProductIds, true)) {
        $reviewErrors[] = 'That product is not part of this order.';
    }
    if ($reviewerName === '' || mb_strlen($reviewerName) > 100) {
        $reviewErrors[] = 'Please enter your name (max 100 characters).';
    }
    if ($comment === '' || mb_strlen($comment) > 4000) {
        $reviewErrors[] = 'Please write your review (max 4000 characters).';
    }

    if (!$reviewErrors) {
        $stmt = $conn->prepare("INSERT INTO Reviews (product_id, reviewer_name, comment, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$productId, $reviewerName, $comment]);
        $_SESSION['review_ids'][] = (int) $conn->lastInsertId();
        flash('success', 'Thank you! Your review has been submitted and is awaiting admin approval.');
        header('Location: invoice.php?order=' . $orderId);
        exit;
    }
}

$myOrderReviews = [];
$myIds = array_values(array_filter(array_map('intval', $_SESSION['review_ids'] ?? [])));
if ($myIds && $orderProductIds) {
    $phProducts = implode(',', array_fill(0, count($orderProductIds), '?'));
    $phReviews  = implode(',', array_fill(0, count($myIds), '?'));
    $stmt = $conn->prepare(
        "SELECT review_id, product_id, comment, status, created_at FROM Reviews
         WHERE product_id IN ($phProducts) AND review_id IN ($phReviews)
         ORDER BY created_at DESC, review_id DESC"
    );
    $stmt->execute(array_merge($orderProductIds, $myIds));
    $myOrderReviews = $stmt->fetchAll();
}
$reviewsByProduct = [];
foreach ($myOrderReviews as $r) {
    $reviewsByProduct[(int) $r['product_id']][] = $r;
}

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

    <?php if (empty($_GET['print']) && $orderProductIds): ?>
        <div class="card shadow invoice-card mx-auto mt-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-star-half me-1 text-warning"></i>Rate Your Purchases</h5>
                <p class="text-muted small mb-4">Share your experience — reviews are published on the product page after admin approval.</p>

                <?php if ($reviewErrors): ?>
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-3"><?php foreach ($reviewErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <div class="row g-3">
                    <?php foreach ($items as $item): if (empty($item['product_id'])) continue; ?>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-baseline mb-2">
                                    <strong><?= e($item['name']) ?></strong>
                                    <a class="small" href="product-detail.php?id=<?= (int) $item['product_id'] ?>">view product</a>
                                </div>

                                <?php foreach ($reviewsByProduct[(int) $item['product_id']] ?? [] as $r): ?>
                                    <div class="d-flex justify-content-between align-items-start gap-2 bg-light rounded p-2 mb-2">
                                        <p class="small text-muted mb-0"><?= nl2br(e($r['comment'])) ?></p>
                                        <?php if ($r['status'] === 'approved'): ?>
                                            <span class="badge bg-success text-nowrap">Admin approved your review</span>
                                        <?php elseif ($r['status'] === 'declined'): ?>
                                            <span class="badge bg-danger text-nowrap">Declined</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark text-nowrap">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <form method="post" novalidate>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="add_review">
                                    <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                    <div class="mb-2">
                                        <input type="text" class="form-control form-control-sm" name="reviewer_name" required maxlength="100"
                                               value="<?= e($_POST['reviewer_name'] ?? $order['customer_name']) ?>" aria-label="Your name">
                                    </div>
                                    <div class="d-flex gap-2">
                                        <textarea class="form-control form-control-sm" name="comment" rows="2" required maxlength="4000"
                                                  placeholder="How was this product?"><?= e($_POST['comment'] ?? '') ?></textarea>
                                        <button type="submit" class="btn btn-primary btn-sm text-nowrap"><i class="bi bi-send me-1"></i>Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($_GET['print'])): ?>
        <div class="text-center mt-4 no-print">
            <button class="btn btn-outline-primary me-2" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print / Save as PDF</button>
            <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
