<?php
// includes/product-card.php — expects $p (product row)
?>
<div class="col">
    <div class="card h-100 product-card shadow-sm">
        <a href="product-detail.php?id=<?= (int) $p['product_id'] ?>" class="text-decoration-none">
            <img src="<?= e($p['image_url'] ?: 'assets/uploads/product-placeholder.svg') ?>" class="card-img-top product-img" alt="<?= e($p['name']) ?>">
        </a>
        <div class="card-body d-flex flex-column">
            <h6 class="card-title mb-1"><?= e($p['name']) ?></h6>
            <?php if (!empty($p['category_name'])): ?>
                <small class="text-muted"><?= e($p['category_name']) ?></small>
            <?php endif; ?>
            <div class="mt-auto d-flex justify-content-between align-items-center pt-2">
                <span class="fw-bold text-primary"><?= currency($p['price']) ?></span>
                <?php if ((int) $p['stock'] > 0): ?>
                    <form action="cart.php" method="post" class="m-0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= (int) $p['product_id'] ?>">
                        <button class="btn btn-sm btn-primary"><i class="bi bi-cart-plus"></i> Add</button>
                    </form>
                <?php else: ?>
                    <span class="badge bg-secondary">Out of stock</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
