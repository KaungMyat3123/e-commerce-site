<?php
// cart.php — shopping cart management (sessions)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cart.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['product_id'] ?? 0);

    if ($id > 0) {
        switch ($action) {
            case 'add':
                $stmt = $conn->prepare('SELECT name, stock FROM Products WHERE product_id = ?');
                $stmt->execute([$id]);
                $product = $stmt->fetch();
                if (!$product) {
                    flash('error', 'Product not found.');
                } elseif ((int) $product['stock'] <= 0) {
                    flash('error', '"' . $product['name'] . '" is out of stock.');
                } else {
                    $qty = max(1, min((int) ($_POST['quantity'] ?? 1), (int) $product['stock']));
                    cart_add($id, $qty);
                    flash('success', '"' . $product['name'] . '" added to your cart.');
                }
                break;
            case 'update':
                cart_update($id, (int) ($_POST['quantity'] ?? 0));
                flash('success', 'Cart updated.');
                break;
            case 'remove':
                cart_remove($id);
                flash('success', 'Item removed from cart.');
                break;
        }
    }

    header('Location: cart.php');
    exit;
}

if (($_GET['clear'] ?? '') === '1') {
    cart_clear();
    flash('success', 'Cart cleared.');
    header('Location: cart.php');
    exit;
}

$items = cart_items($conn);
$total = array_sum(array_column($items, 'line_total'));

$pageTitle  = 'Shopping Cart';
$activePage = 'cart';
require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <h1 class="h3 fw-bold mb-4">Shopping Cart</h1>

    <?php if (!$items): ?>
        <div class="alert alert-light border text-center py-5">
            <i class="bi bi-cart-x display-4 text-muted"></i>
            <p class="mt-3 mb-2">Your cart is empty.</p>
            <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Price</th>
                            <th class="text-center" style="width:130px">Qty</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-center" style="width:70px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="d-flex align-items-center gap-2">
                                    <img src="<?= e($item['image_url'] ?: 'assets/uploads/product-placeholder.svg') ?>" alt="" width="48" height="48" class="rounded object-fit-cover">
                                    <a href="product-detail.php?id=<?= (int) $item['product_id'] ?>" class="text-decoration-none"><?= e($item['name']) ?></a>
                                </td>
                                <td class="text-center"><?= currency($item['price']) ?></td>
                                <td>
                                    <form method="post" class="d-flex gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                        <input type="number" name="quantity" min="1" max="<?= (int) $item['stock'] ?>" value="<?= (int) $item['quantity'] ?>" class="form-control form-control-sm text-center">
                                        <button class="btn btn-sm btn-outline-secondary" title="Update"><i class="bi bi-arrow-clockwise"></i></button>
                                    </form>
                                </td>
                                <td class="text-end fw-semibold"><?= currency($item['line_total']) ?></td>
                                <td class="text-center">
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mt-4 gy-3">
            <div class="col-md-6">
                <a href="shop.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Continue Shopping</a>
                <a href="cart.php?clear=1" class="btn btn-outline-danger ms-2" onclick="return confirm('Remove all items from the cart?')">Clear Cart</a>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2"><span>Items (<?= cart_count() ?>)</span><span><?= currency($total) ?></span></div>
                        <div class="d-flex justify-content-between mb-3"><span>Shipping</span><span class="text-success">Free</span></div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span><?= currency($total) ?></span></div>
                        <a href="checkout.php" class="btn btn-primary w-100 mt-3">Proceed to Checkout <i class="bi bi-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
