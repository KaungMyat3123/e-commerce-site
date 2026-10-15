<?php
// admin/payments.php — payment method CRUD
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $postAction = $_POST['post_action'] ?? '';
    $editId = (int) ($_POST['payment_method_id'] ?? 0);

    if ($postAction === 'delete') {
        try {
            $conn->prepare('DELETE FROM PaymentMethods WHERE payment_method_id = ?')->execute([$editId]);
            flash('success', 'Payment method deleted.');
        } catch (PDOException $ex) {
            // Orders may reference this method; deactivate instead.
            $conn->prepare('UPDATE PaymentMethods SET is_active = 0 WHERE payment_method_id = ?')->execute([$editId]);
            flash('error', 'Method is used by existing orders — deactivated instead.');
        }
    } else {
        $name = trim($_POST['method_name'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '' || mb_strlen($name) > 100) {
            flash('error', 'Method name is required (max 100 characters).');
        } elseif ($postAction === 'create') {
            $conn->prepare('INSERT INTO PaymentMethods (method_name, is_active) VALUES (?, ?)')->execute([$name, $active]);
            flash('success', 'Payment method added.');
        } elseif ($postAction === 'update' && $editId > 0) {
            $conn->prepare('UPDATE PaymentMethods SET method_name = ?, is_active = ? WHERE payment_method_id = ?')->execute([$name, $active, $editId]);
            flash('success', 'Payment method updated.');
        }
    }
    header('Location: payments.php');
    exit;
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare('SELECT * FROM PaymentMethods WHERE payment_method_id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}

$methods = $conn->query('SELECT * FROM PaymentMethods ORDER BY method_name')->fetchAll();

$adminTitle = 'Payment Methods';
$adminActive = 'payments';
require __DIR__ . '/../includes/admin-header.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-bold"><?= $editing ? 'Edit Method' : 'Add Method' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="post_action" value="<?= $editing ? 'update' : 'create' ?>">
                    <input type="hidden" name="payment_method_id" value="<?= (int) ($editing['payment_method_id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label class="form-label">Method Name *</label>
                        <input type="text" name="method_name" class="form-control" required maxlength="100" value="<?= e($editing['method_name'] ?? '') ?>">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= !isset($editing['is_active']) || !empty($editing['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Active (available at checkout)</label>
                    </div>
                    <button class="btn btn-primary"><?= $editing ? 'Update' : 'Add' ?></button>
                    <?php if ($editing): ?><a href="payments.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header fw-bold">All Methods (<?= count($methods) ?>)</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Status</th><th>Added</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($methods as $m): ?>
                            <tr>
                                <td><?= (int) $m['payment_method_id'] ?></td>
                                <td class="fw-semibold"><?= e($m['method_name']) ?></td>
                                <td><span class="badge bg-<?= $m['is_active'] ? 'success' : 'secondary' ?>"><?= $m['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                <td class="small text-muted"><?= e(date('d M Y', strtotime($m['created_at']))) ?></td>
                                <td class="text-end">
                                    <a href="payments.php?edit=<?= (int) $m['payment_method_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this payment method?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="post_action" value="delete">
                                        <input type="hidden" name="payment_method_id" value="<?= (int) $m['payment_method_id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$methods): ?><tr><td colspan="5" class="text-center text-muted py-4">No payment methods yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
