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
    'SELECT product_id, name, price, stock, image_url FROM Products WHERE category_id = ? AND product_id <> ? ORDER BY ' . db_random_order() . ' LIMIT 4'
);
$related->execute([$product['category_id'], $product['product_id']]);
$relatedProducts = $related->fetchAll();

// ---- Reviews: submit handler ----
$reviewErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_review') {
    csrf_verify();
    $reviewerName = trim($_POST['reviewer_name'] ?? '');
    $comment      = trim($_POST['comment'] ?? '');

    if ($reviewerName === '' || mb_strlen($reviewerName) > 100) {
        $reviewErrors[] = 'Please enter your name (max 100 characters).';
    }
    if ($comment === '' || mb_strlen($comment) > 4000) {
        $reviewErrors[] = 'Please write your review (max 4000 characters).';
    }

    if (!$reviewErrors) {
        $stmt = $conn->prepare("INSERT INTO Reviews (product_id, reviewer_name, comment, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$product['product_id'], $reviewerName, $comment]);
        $_SESSION['review_ids'][] = (int) $conn->lastInsertId();
        flash('success', 'Thank you! Your review has been submitted and is awaiting admin approval.');
        header('Location: product-detail.php?id=' . (int) $product['product_id']);
        exit;
    }
}

// ---- Reviews: public approved list + this visitor's own reviews ----
$stmt = $conn->prepare(
    "SELECT reviewer_name, comment, created_at FROM Reviews
     WHERE product_id = ? AND status = 'approved'
     ORDER BY created_at DESC, review_id DESC"
);
$stmt->execute([$product['product_id']]);
$approvedReviews = $stmt->fetchAll();

$myReviews = [];
$myIds = array_values(array_filter(array_map('intval', $_SESSION['review_ids'] ?? [])));
if ($myIds) {
    $placeholders = implode(',', array_fill(0, count($myIds), '?'));
    $stmt = $conn->prepare(
        "SELECT review_id, comment, status, created_at FROM Reviews
         WHERE product_id = ? AND review_id IN ($placeholders)
         ORDER BY created_at DESC, review_id DESC"
    );
    $stmt->execute(array_merge([$product['product_id']], $myIds));
    $myReviews = $stmt->fetchAll();
}

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

    <hr class="my-5">
    <h3 class="h5 fw-bold mb-4">Customer Reviews</h3>
    <div class="row gy-4">
        <div class="col-lg-7">
            <?php if ($myReviews): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-bold bg-light">Your reviews</div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($myReviews as $r): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <p class="mb-1 text-muted"><?= nl2br(e($r['comment'])) ?></p>
                                    <?php if ($r['status'] === 'approved'): ?>
                                        <span class="badge bg-success text-nowrap">Admin approved your review</span>
                                    <?php elseif ($r['status'] === 'declined'): ?>
                                        <span class="badge bg-danger text-nowrap">Declined</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark text-nowrap">Pending</span>
                                    <?php endif; ?>
                                </div>
                                <span class="small text-muted"><?= e(date('d M Y, h:i A', strtotime($r['created_at']))) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($approvedReviews): ?>
                <?php foreach ($approvedReviews as $r): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-baseline">
                            <span class="fw-semibold"><i class="bi bi-person-circle me-1 text-primary"></i><?= e($r['reviewer_name']) ?></span>
                            <span class="small text-muted"><?= e(date('d M Y', strtotime($r['created_at']))) ?></span>
                        </div>
                        <p class="mb-0 mt-1 text-muted"><?= nl2br(e($r['comment'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php elseif (!$myReviews): ?>
                <p class="text-muted">No reviews yet. Be the first to share your thoughts!</p>
            <?php endif; ?>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="h6 mb-3">Write a Review</h4>
                    <?php if ($reviewErrors): ?>
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0 ps-3"><?php foreach ($reviewErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <form method="post" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add_review">
                        <div class="mb-3">
                            <label for="reviewer_name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="reviewer_name" name="reviewer_name" required maxlength="100" value="<?= e($_POST['reviewer_name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="comment" class="form-label">Your Review</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" required maxlength="4000"><?= e($_POST['comment'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-chat-left-text me-1"></i>Submit Review</button>
                        <p class="small text-muted mt-2 mb-0">Reviews are published after admin approval.</p>
                    </form>
                </div>
            </div>
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
