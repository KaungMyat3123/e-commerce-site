<?php
// shop.php — product listing with search, filters, sorting and pagination
require_once __DIR__ . '/includes/db.php';

$perPage = 8;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$search  = trim($_GET['search'] ?? '');
$category = (int) ($_GET['category'] ?? 0);
$sort    = $_GET['sort'] ?? 'newest';
$minPrice = ($_GET['min_price'] ?? '') !== '' ? (float) $_GET['min_price'] : null;
$maxPrice = ($_GET['max_price'] ?? '') !== '' ? (float) $_GET['max_price'] : null;

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($category > 0) {
    $where[]  = 'p.category_id = ?';
    $params[] = $category;
}
if ($minPrice !== null) {
    $where[]  = 'p.price >= ?';
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    $where[]  = 'p.price <= ?';
    $params[] = $maxPrice;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderMap = [
    'newest'    => 'p.created_at DESC',
    'price_asc' => 'p.price ASC',
    'price_desc'=> 'p.price DESC',
    'name_asc'  => 'p.name ASC',
];
$orderSql = $orderMap[$sort] ?? $orderMap['newest'];

$countStmt = $conn->prepare("SELECT COUNT(*) FROM Products p $whereSql");
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();
$totalPages    = max(1, (int) ceil($totalProducts / $perPage));
$page          = min($page, $totalPages);

$sql = "SELECT p.product_id, p.name, p.price, p.stock, p.image_url, c.category_name
        FROM Products p LEFT JOIN Categories c ON c.category_id = p.category_id
        $whereSql
        ORDER BY $orderSql
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
foreach ($params as $i => $v) {
    $stmt->bindValue($i + 1, $v);
}
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$categories = $conn->query('SELECT category_id, category_name FROM Categories ORDER BY category_name')->fetchAll();

// Build pagination links preserving current filters
$queryBase = array_filter([
    'search' => $search, 'category' => $category ?: null, 'sort' => $sort,
    'min_price' => $minPrice, 'max_price' => $maxPrice,
], fn ($v) => $v !== null && $v !== '');
$makeUrl = fn (int $p) => 'shop.php?' . http_build_query($queryBase + ['page' => $p]);

$pageTitle  = 'Shop';
$activePage = 'shop';
require __DIR__ . '/includes/header.php';
?>

<section class="container py-4">
    <h1 class="h3 fw-bold mb-4">Shop</h1>
    <div class="row gy-4">
        <aside class="col-lg-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="get" action="shop.php" class="mb-3">
                        <input type="hidden" name="category" value="<?= $category ?: '' ?>">
                        <input type="hidden" name="sort" value="<?= e($sort) ?>">
                        <div class="input-group">
                            <input type="search" class="form-control" name="search" placeholder="Search products..." value="<?= e($search) ?>">
                            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                        </div>
                    </form>

                    <h6 class="fw-bold">Categories</h6>
                    <div class="list-group list-group-flush mb-3">
                        <a href="<?= 'shop.php?' . http_build_query(array_diff_key($queryBase, ['category' => 1])) ?>"
                           class="list-group-item list-group-item-action <?= $category === 0 ? 'active' : '' ?>">All Categories</a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="<?= 'shop.php?' . http_build_query(array_merge($queryBase, ['category' => $cat['category_id']])) ?>"
                               class="list-group-item list-group-item-action <?= $category === (int) $cat['category_id'] ? 'active' : '' ?>"><?= e($cat['category_name']) ?></a>
                        <?php endforeach; ?>
                    </div>

                    <h6 class="fw-bold">Price Range</h6>
                    <form method="get" action="shop.php" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="search" value="<?= e($search) ?>">
                        <input type="hidden" name="category" value="<?= $category ?: '' ?>">
                        <input type="hidden" name="sort" value="<?= e($sort) ?>">
                        <input type="number" step="0.01" min="0" name="min_price" class="form-control form-control-sm" placeholder="Min" value="<?= e((string) ($minPrice ?? '')) ?>">
                        <span class="text-muted">&ndash;</span>
                        <input type="number" step="0.01" min="0" name="max_price" class="form-control form-control-sm" placeholder="Max" value="<?= e((string) ($maxPrice ?? '')) ?>">
                        <button class="btn btn-sm btn-outline-primary">Go</button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <span class="text-muted"><?= $totalProducts ?> product<?= $totalProducts === 1 ? '' : 's' ?> found</span>
                <form method="get" action="shop.php" class="d-flex gap-2">
                    <?php foreach (['search' => $search, 'category' => $category ?: '', 'min_price' => $minPrice ?? '', 'max_price' => $maxPrice ?? ''] as $k => $v): ?>
                        <input type="hidden" name="<?= $k ?>" value="<?= e((string) $v) ?>">
                    <?php endforeach; ?>
                    <select name="sort" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A&ndash;Z</option>
                    </select>
                </form>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
                <?php foreach ($products as $p): ?>
                    <?php require __DIR__ . '/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
            <?php if (!$products): ?>
                <div class="alert alert-light border text-center mt-3">No products match your search.</div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $makeUrl($page - 1) ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $makeUrl($i) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $makeUrl($page + 1) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
