<?php
// checkout.php — order placement & payment selection
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cart.php';

$errors = [];
$old = ['customer_name' => '', 'customer_email' => '', 'customer_phone' => '', 'shipping_address' => '', 'payment_method_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (array_keys($old) as $key) {
        $old[$key] = trim($_POST[$key] ?? '');
    }

    if ($old['customer_name'] === '' || mb_strlen($old['customer_name']) > 100) {
        $errors['customer_name'] = 'Full name is required.';
    }
    if (!filter_var($old['customer_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['customer_email'] = 'A valid email address is required.';
    }
    if (!preg_match('/^[0-9+()\-\s]{6,20}$/', $old['customer_phone'])) {
        $errors['customer_phone'] = 'A valid phone number is required.';
    }
    if ($old['shipping_address'] === '' || mb_strlen($old['shipping_address']) > 2000) {
        $errors['shipping_address'] = 'Shipping address is required.';
    }

    $items = cart_items($conn);
    if (!$items) {
        $errors['cart'] = 'Your cart is empty.';
    }

    $paymentStmt = $conn->prepare('SELECT payment_method_id, method_name FROM PaymentMethods WHERE is_active = 1');
    $paymentStmt->execute();
    $payments = $paymentStmt->fetchAll();
    $paymentIds = array_column($payments, 'payment_method_id');

    if (!$old['payment_method_id'] || !in_array((int) $old['payment_method_id'], array_map('intval', $paymentIds), true)) {
        $errors['payment_method_id'] = 'Please choose a valid payment method.';
    }

    if (!$errors) {
        try {
            $conn->beginTransaction();

            $total = array_sum(array_column($items, 'line_total'));

            $stmt = $conn->prepare(
                'INSERT INTO Orders (customer_name, customer_email, customer_phone, shipping_address, total_amount, order_status, payment_method_id)
                 VALUES (?, ?, ?, ?, ?, \'Pending\', ?)'
            );
            $stmt->execute([$old['customer_name'], $old['customer_email'], $old['customer_phone'], $old['shipping_address'], $total, (int) $old['payment_method_id']]);
            $orderId = (int) $conn->lastInsertId();

            $itemStmt  = $conn->prepare('INSERT INTO OrderItems (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
            $stockStmt = $conn->prepare('UPDATE Products SET stock = stock - ? WHERE product_id = ? AND stock >= ?');

            foreach ($items as $item) {
                $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                $stockStmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
                if ($stockStmt->rowCount() === 0) {
                    throw new RuntimeException('"' . $item['name'] . '" ran out of stock while placing the order.');
                }
            }

            $conn->commit();
            cart_clear();
            header('Location: invoice.php?order=' . $orderId);
            exit;
        } catch (RuntimeException $ex) {
            $conn->rollBack();
            $errors['general'] = $ex->getMessage();
        }
    }
}

$items = cart_items($conn);
$total = array_sum(array_column($items, 'line_total'));

$payments = $conn->query('SELECT payment_method_id, method_name FROM PaymentMethods WHERE is_active = 1 ORDER BY method_name')->fetchAll();

$pageTitle  = 'Checkout';
$activePage = 'checkout';
require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <h1 class="h3 fw-bold mb-4">Checkout</h1>

    <?php if (!$items): ?>
        <div class="alert alert-warning">Your cart is empty. <a href="shop.php" class="alert-link">Go shopping</a> first.</div>
    <?php else: ?>
        <div class="row gy-4">
            <div class="col-lg-7">
                <?php if ($errors): ?>
                    <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form method="post">
                            <?= csrf_field() ?>
                            <h5 class="fw-bold mb-3">Shipping Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_name">Full Name *</label>
                                    <input type="text" class="form-control <?= isset($errors['customer_name']) ? 'is-invalid' : '' ?>" id="customer_name" name="customer_name" required maxlength="100" value="<?= e($old['customer_name']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_email">Email *</label>
                                    <input type="email" class="form-control <?= isset($errors['customer_email']) ? 'is-invalid' : '' ?>" id="customer_email" name="customer_email" required value="<?= e($old['customer_email']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_phone">Phone *</label>
                                    <input type="tel" class="form-control <?= isset($errors['customer_phone']) ? 'is-invalid' : '' ?>" id="customer_phone" name="customer_phone" required value="<?= e($old['customer_phone']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="shipping_address">Shipping Address *</label>
                                    <textarea class="form-control <?= isset($errors['shipping_address']) ? 'is-invalid' : '' ?>" id="shipping_address" name="shipping_address" rows="3" required><?= e($old['shipping_address']) ?></textarea>
                                </div>
                            </div>

                            <h5 class="fw-bold mt-4 mb-3">Payment Method</h5>
                            <?php if ($payments): ?>
                                <div class="list-group mb-3">
                                    <?php foreach ($payments as $pm): ?>
                                        <label class="list-group-item d-flex align-items-center gap-2">
                                            <input class="form-check-input mt-0" type="radio" name="payment_method_id" value="<?= (int) $pm['payment_method_id'] ?>" required <?= (int) $old['payment_method_id'] === (int) $pm['payment_method_id'] ? 'checked' : '' ?>>
                                            <span><i class="bi bi-credit-card me-2 text-primary"></i><?= e($pm['method_name']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">No payment methods are currently available. Please contact support.</div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary btn-lg w-100" <?= $payments ? '' : 'disabled' ?>>
                                <i class="bi bi-check-circle me-1"></i>Place Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm order-summary">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Order Summary</h5>
                        <?php foreach ($items as $item): ?>
                            <div class="d-flex justify-content-between small mb-2">
                                <span><?= e($item['name']) ?> &times; <?= (int) $item['quantity'] ?></span>
                                <span><?= currency($item['line_total']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold"><span>Total</span><span><?= currency($total) ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
