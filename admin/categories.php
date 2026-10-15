<?php
// admin/categories.php — category CRUD
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$action = $_GET['action'] ?? 'list';
$id     = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $postAction = $_POST['post_action'] ?? '';

    if ($postAction === 'delete') {
        $deleteId = (int) ($_POST['category_id'] ?? 0);
        // Products referencing this category would block deletion (FK); null them out first.
        $conn->beginTransaction();
        $conn->prepare('UPDATE Products SET category_id = NULL WHERE category_id = ?')->execute([$deleteId]);
        $conn->prepare('DELETE FROM Categories WHERE category_id = ?')->execute([$deleteId]);
        $conn->commit();
        flash('success', 'Category deleted.');
        header('Location: categories.php');
        exit;
    }

    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $editId = (int) ($_POST['category_id'] ?? 0);

    if ($name === '' || mb_strlen($name) > 100) {
        flash('error', 'Category name is required (max 100 characters).');
        header('Location: categories.php?' . http_build_query($editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'new']));
        exit;
    }

    if ($postAction === 'create') {
        $stmt = $conn->prepare('INSERT INTO Categories (category_name, description) VALUES (?, ?)');
        $stmt->execute([$name, $description]);
        flash('success', 'Category created.');
    } elseif ($postAction === 'update' && $editId > 0) {
        $stmt = $conn->prepare('UPDATE Categories SET category_name = ?, description = ? WHERE category_id = ?');
        $stmt->execute([$name, $description, $editId]);
        flash('success', 'Category updated.');
    }
    header('Location: categories.php');
    exit;
}

$editing = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $conn->prepare('SELECT * FROM Categories WHERE category_id = ?');
    $stmt->execute([$id]);
    $editing = $stmt->fetch();
}

$categories = $conn->query(
    'SELECT c.*, (SELECT COUNT(*) FROM Products p WHERE p.category_id = c.category_id) AS product_count
     FROM Categories c ORDER BY c.category_name'
)->fetchAll();

$adminTitle = 'Category Management';
$adminActive = 'categories';
require __DIR__ . '/../includes/admin-header.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-bold"><?= $editing ? 'Edit Category' : 'Add Category' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="post_action" value="<?= $editing ? 'update' : 'create' ?>">
                    <input type="hidden" name="category_id" value="<?= (int) ($editing['category_id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="category_name" class="form-control" required maxlength="100" value="<?= e($editing['category_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($editing['description'] ?? '') ?></textarea>
                    </div>
                    <button class="btn btn-primary"><?= $editing ? 'Update' : 'Create' ?></button>
                    <?php if ($editing): ?><a href="categories.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header fw-bold">All Categories (<?= count($categories) ?>)</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Description</th><th class="text-center">Products</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($categories as $c): ?>
                            <tr>
                                <td><?= (int) $c['category_id'] ?></td>
                                <td class="fw-semibold"><?= e($c['category_name']) ?></td>
                                <td class="small text-muted text-truncate" style="max-width:250px"><?= e($c['description'] ?? '') ?></td>
                                <td class="text-center"><?= (int) $c['product_count'] ?></td>
                                <td class="text-end">
                                    <a href="categories.php?action=edit&id=<?= (int) $c['category_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this category? Its products will become uncategorized.')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="post_action" value="delete">
                                        <input type="hidden" name="category_id" value="<?= (int) $c['category_id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$categories): ?><tr><td colspan="5" class="text-center text-muted py-4">No categories yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
