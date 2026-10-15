<?php
// product-detail.php — individual product view
require_once __DIR__ . '/includes/db.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare(
    'SELECT p.*, c.category_name FROM Products p LEFT JOIN Categories c ON c.category_id = p.category_id WHERE p.product_id = ?'
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container py-5"><div class="alert alert-warning">Sorry, that product could not be found. <a href="shop.php" class="alert-link">Back to shop</a>.</div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$related = $conn->prepare(
    'SELECT product_id, name, price, stock, image_url FROM Products WHERE category_id = ? AND product_id <> ? ORDER BY RANDOM() LIMIT 4'
);
$related->execute([$product['category_id'], $product['product_id']]);
$relatedProducts = $related->fetchAll();

$pageTitle  = $product['name'];
$activePage = 'shop';
require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
            <?php if ($product['category_name']): ?>
                <li class="breadcrumb-item"><a href="shop.php?category=<?= (int) $product['category_id'] ?>"><?= e($product['category_name']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?= e($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row gy-4">
        <div class="col-md-5">
            <img src="<?= e($product['image_url'] ?: 'assets/uploads/product-placeholder.svg') ?>" class="img-fluid rounded shadow-sm product-detail-img" alt="<?= e($product['name']) ?>">
        </div>
        <div class="col-md-6">
            <h1 class="h3 fw-bold"><?= e($product['name']) ?></h1>
            <p class="text-muted"><?= e($product['category_name'] ?? 'Uncategorized') ?></p>
            <h2 class="h4 text-primary fw-bold mb-3"><?= currency($product['price']) ?></h2>

            <table class="table table-sm w-auto mb-4">
                <tbody>
                    <tr><th class="text-muted">Size</th><td><?= e($product['size'] ?: '—') ?></td></tr>
                    <tr><th class="text-muted">Color</th><td><?= e($product['color'] ?: '—') ?></td></tr>
                    <tr><th class="text-muted">Material</th><td><?= e($product['material'] ?: '—') ?></td></tr>
                    <tr>
                        <th class="text-muted">Availability</th>
                        <td>
                            <?php if ((int) $product['stock'] > 0): ?>
                                <span class="badge bg-success"><?= (int) $product['stock'] ?> in stock</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Out of stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php if ((int) $product['stock'] > 0): ?>
                <form action="cart.php" method="post" class="d-flex gap-2 align-items-center mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
                    <input type="number" name="quantity" class="form-control" style="width:90px" min="1" max="<?= (int) $product['stock'] ?>" value="1">
                    <button class="btn btn-primary btn-lg"><i class="bi bi-cart-plus me-1"></i>Add to Cart</button>
                </form>
            <?php endif; ?>

            <h5 class="fw-bold">Description</h5>
            <p class="text-muted"><?= nl2br(e($product['description'] ?? '')) ?></p>
        </div>
    </div>

    <?php if ($relatedProducts): ?>
        <hr class="my-5">
        <h3 class="h5 fw-bold mb-4">You may also like</h3>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
            <?php foreach ($relatedProducts as $p): ?>
                <?php $p['category_name'] = null; require __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
