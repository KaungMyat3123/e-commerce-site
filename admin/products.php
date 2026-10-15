<?php
// admin/products.php — product CRUD with image upload
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$action = $_GET['action'] ?? 'list';
$id     = (int) ($_GET['id'] ?? 0);
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $postAction = $_POST['post_action'] ?? '';

    if ($postAction === 'delete') {
        $deleteId = (int) ($_POST['product_id'] ?? 0);
        try {
            // OrderItems reference Products; try hard delete, fall back to hiding the row.
            $stmt = $conn->prepare('DELETE FROM Products WHERE product_id = ?');
            $stmt->execute([$deleteId]);
            flash('success', 'Product deleted.');
        } catch (PDOException $ex) {
            $conn->prepare('UPDATE Products SET stock = 0 WHERE product_id = ?')->execute([$deleteId]);
            flash('error', 'Product has existing orders — set to out of stock instead of deleting.');
        }
        header('Location: products.php');
        exit;
    }

    $fields = [
        'category_id' => (int) ($_POST['category_id'] ?? 0) ?: null,
        'name'        => trim($_POST['name'] ?? ''),
        'size'        => trim($_POST['size'] ?? ''),
        'color'       => trim($_POST['color'] ?? ''),
        'material'    => trim($_POST['material'] ?? ''),
        'price'       => (float) ($_POST['price'] ?? 0),
        'stock'       => max(0, (int) ($_POST['stock'] ?? 0)),
        'description' => trim($_POST['description'] ?? ''),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
    ];
    $editId = (int) ($_POST['product_id'] ?? 0);

    if ($fields['name'] === '' || $fields['price'] < 0) {
        flash('error', 'Product name is required and price must be zero or greater.');
        header('Location: products.php?' . http_build_query($editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'new']));
        exit;
    }

    // Handle image upload
    $imageUrl = null;
    $imageError = null;
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $sizeOk = ($file['size'] ?? 0) > 0 && $file['size'] <= 3 * 1024 * 1024;

        if ($file['error'] === UPLOAD_ERR_OK && in_array($ext, $allowed, true) && $sizeOk) {
            $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = __DIR__ . '/../assets/uploads/' . $safeName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $imageUrl = 'assets/uploads/' . $safeName;
            } else {
                $imageError = 'Could not save the uploaded file.';
            }
        } else {
            $imageError = 'Invalid image (allowed: jpg, png, gif, webp, svg — max 3 MB).';
        }
    }

    if ($imageError) {
        flash('error', $imageError);
        header('Location: products.php?' . http_build_query($editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'new']));
        exit;
    }

    $common = [
        $fields['category_id'], $fields['name'], $fields['size'], $fields['color'],
        $fields['material'], $fields['price'], $fields['stock'], $fields['description'],
    ];

    if ($postAction === 'create') {
        $stmt = $conn->prepare(
            'INSERT INTO Products (category_id, name, size, color, material, price, stock, description, image_url, is_featured)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(array_merge($common, [$imageUrl, $fields['is_featured']]));
        flash('success', 'Product created.');
    } elseif ($postAction === 'update' && $editId > 0) {
        if ($imageUrl !== null) {
            $stmt = $conn->prepare(
                'UPDATE Products SET category_id = ?, name = ?, size = ?, color = ?, material = ?, price = ?, stock = ?, description = ?, image_url = ?, is_featured = ? WHERE product_id = ?'
            );
            $stmt->execute(array_merge($common, [$imageUrl, $fields['is_featured'], $editId]));
        } else {
            $stmt = $conn->prepare(
                'UPDATE Products SET category_id = ?, name = ?, size = ?, color = ?, material = ?, price = ?, stock = ?, description = ?, is_featured = ? WHERE product_id = ?'
            );
            $stmt->execute(array_merge($common, [$fields['is_featured'], $editId]));
        }
        flash('success', 'Product updated.');
    }
    header('Location: products.php');
    exit;
}

$editing = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $conn->prepare('SELECT * FROM Products WHERE product_id = ?');
    $stmt->execute([$id]);
    $editing = $stmt->fetch();
}

$categories = $conn->query('SELECT category_id, category_name FROM Categories ORDER BY category_name')->fetchAll();

// Listing with search + pagination
$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE p.name LIKE ?';
    $params[] = '%' . $search . '%';
}

$countStmt = $conn->prepare("SELECT COUNT(*) FROM Products p $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);

$stmt = $conn->prepare(
    "SELECT p.*, c.category_name FROM Products p LEFT JOIN Categories c ON c.category_id = p.category_id
     $where ORDER BY p.created_at DESC, p.product_id DESC
     LIMIT ? OFFSET ?"
);
foreach ($params as $i => $v) {
    $stmt->bindValue($i + 1, $v);
}
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$adminTitle = 'Product Management';
$adminActive = 'products';
require __DIR__ . '/../includes/admin-header.php';
?>

<?php if ($action === 'new' || $editing): ?>
<div class="card">
    <div class="card-header fw-bold"><?= $editing ? 'Edit: ' . e($editing['name']) : 'Add Product' ?></div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="<?= $editing ? 'update' : 'create' ?>">
            <input type="hidden" name="product_id" value="<?= (int) ($editing['product_id'] ?? 0) ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" class="form-control" required maxlength="150" value="<?= e($editing['name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">— None —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['category_id'] ?>" <?= (int) ($editing['category_id'] ?? 0) === (int) $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price *</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" required value="<?= e((string) ($editing['price'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Size</label>
                    <input type="text" name="size" class="form-control" maxlength="50" value="<?= e($editing['size'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Color</label>
                    <input type="text" name="color" class="form-control" maxlength="50" value="<?= e($editing['color'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Material</label>
                    <input type="text" name="material" class="form-control" maxlength="100" value="<?= e($editing['material'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-control" min="0" required value="<?= e((string) ($editing['stock'] ?? '0')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4"><?= e($editing['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Image <?= $editing ? '(replaces current)' : '' ?></label>
                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.svg">
                    <?php if (!empty($editing['image_url'])): ?>
                        <img src="../<?= e($editing['image_url']) ?>" alt="current" class="mt-2" style="max-height:80px">
                    <?php endif; ?>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" <?= !empty($editing['is_featured']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">Featured on homepage</label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary"><?= $editing ? 'Update Product' : 'Create Product' ?></button>
                <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form method="get" class="d-flex gap-2" style="max-width:360px">
            <input type="search" name="search" class="form-control form-control-sm" placeholder="Search products..." value="<?= e($search) ?>">
            <button class="btn btn-sm btn-outline-primary">Search</button>
        </form>
        <a href="products.php?action=new" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Product</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th style="width:60px">Image</th><th>Name</th><th>Category</th><th class="text-end">Price</th><th class="text-center">Stock</th><th class="text-center">Featured</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><img src="../<?= $p['image_url'] ? e($p['image_url']) : 'assets/uploads/product-placeholder.svg' ?>" alt="" style="width:42px;height:42px;object-fit:cover" class="rounded"></td>
                            <td class="fw-semibold"><?= e($p['name']) ?></td>
                            <td class="small text-muted"><?= e($p['category_name'] ?? '—') ?></td>
                            <td class="text-end"><?= currency($p['price']) ?></td>
                            <td class="text-center">
                                <?php if ((int) $p['stock'] <= 5): ?>
                                    <span class="badge bg-danger"><?= (int) $p['stock'] ?></span>
                                <?php else: ?>
                                    <?= (int) $p['stock'] ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= !empty($p['is_featured']) ? '<i class="bi bi-star-fill text-warning"></i>' : '' ?></td>
                            <td class="text-end">
                                <a href="../product-detail.php?id=<?= (int) $p['product_id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                <a href="products.php?action=edit&id=<?= (int) $p['product_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this product?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="post_action" value="delete">
                                    <input type="hidden" name="product_id" value="<?= (int) $p['product_id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$products): ?><tr><td colspan="7" class="text-center text-muted py-4">No products found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav><ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="products.php?<?= e(http_build_query(array_filter(['search' => $search, 'page' => $i], fn ($v) => $v !== ''))) ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                </ul></nav>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
