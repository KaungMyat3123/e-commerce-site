<?php
// index.php — Home page
require_once __DIR__ . '/includes/db.php';

$featured = $conn->query(
    'SELECT product_id, name, price, stock, image_url FROM Products WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 8'
)->fetchAll();

if (!$featured) {
    $featured = $conn->query(
        'SELECT product_id, name, price, stock, image_url FROM Products ORDER BY created_at DESC LIMIT 8'
    )->fetchAll();
}

$categories = $conn->query(
    'SELECT c.category_id, c.category_name, c.description, COUNT(p.product_id) AS product_count
     FROM Categories c LEFT JOIN Products p ON p.category_id = c.category_id
     GROUP BY c.category_id, c.category_name, c.description
     ORDER BY c.category_name'
)->fetchAll();

$pageTitle  = 'Home';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero-banner text-white">
    <div class="container">
        <div class="row align-items-center py-5">
            <div class="col-lg-7">
                <h1 class="display-5 fw-bold">Everything you need, delivered fast.</h1>
                <p class="lead mb-4">Browse hundreds of quality products across electronics, fashion, home &amp; living and sports.</p>
                <a href="shop.php" class="btn btn-primary btn-lg me-2">Shop Now</a>
                <a href="about.php" class="btn btn-outline-light btn-lg">Learn More</a>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <h2 class="h4 fw-bold mb-4">Shop by Category</h2>
    <div class="row g-3">
        <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-md-3">
                <a href="shop.php?category=<?= (int) $cat['category_id'] ?>" class="text-decoration-none">
                    <div class="card text-center category-card h-100">
                        <div class="card-body">
                            <i class="bi bi-grid-1x2-fill fs-2 text-primary"></i>
                            <h6 class="mt-2 mb-1"><?= e($cat['category_name']) ?></h6>
                            <small class="text-muted"><?= (int) $cat['product_count'] ?> items</small>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
        <?php if (!$categories): ?>
            <p class="text-muted">No categories have been created yet.</p>
        <?php endif; ?>
    </div>
</section>

<section class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0">Featured Products</h2>
        <a href="shop.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
        <?php foreach ($featured as $p): ?>
            <?php $p['category_name'] = null; require __DIR__ . '/includes/product-card.php'; ?>
        <?php endforeach; ?>
        <?php if (!$featured): ?>
            <p class="text-muted">No products in the catalog yet.</p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
