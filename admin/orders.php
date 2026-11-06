<?php
// admin/orders.php — order listing with status filter
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];
if (in_array($status, ['Pending', 'Confirmed', 'Shipped', 'Cancelled'], true)) {
    $where[] = 'o.order_status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(o.customer_name LIKE ? OR o.customer_email LIKE ? OR CAST(o.order_id AS CHAR) LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));

$countStmt = $conn->prepare("SELECT COUNT(*) FROM Orders o $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);

$stmt = $conn->prepare(
    "SELECT o.*, pm.method_name FROM Orders o LEFT JOIN PaymentMethods pm ON pm.payment_method_id = o.payment_method_id
     $whereSql ORDER BY o.created_at DESC, o.order_id DESC
     LIMIT ? OFFSET ?"
);
foreach ($params as $i => $v) {
    $stmt->bindValue($i + 1, $v);
}
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

$badgeMap = ['Pending' => 'warning', 'Confirmed' => 'success', 'Shipped' => 'primary', 'Cancelled' => 'secondary'];

$adminTitle = 'Order Management';
$adminActive = 'orders';
require __DIR__ . '/../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="get" class="d-flex gap-2">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <input type="search" name="search" class="form-control form-control-sm" placeholder="Search by name, email, order #..." value="<?= e($search) ?>" style="width:260px">
        <button class="btn btn-sm btn-outline-primary">Search</button>
    </form>
    <div class="btn-group">
        <a href="orders.php?<?= e(http_build_query(array_filter(['search' => $search]))) ?>" class="btn btn-sm btn-outline-secondary <?= $status === '' ? 'active' : '' ?>">All</a>
        <?php foreach (['Pending', 'Confirmed', 'Shipped', 'Cancelled'] as $s): ?>
            <a href="orders.php?<?= e(http_build_query(array_filter(['status' => $s, 'search' => $search]))) ?>" class="btn btn-sm btn-outline-secondary <?= $status === $s ? 'active' : '' ?>"><?= $s ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Order #</th><th>Customer</th><th>Contact</th><th>Payment</th><th class="text-end">Items Total</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="fw-semibold">#<?= (int) $o['order_id'] ?></td>
                        <td><?= e($o['customer_name']) ?></td>
                        <td class="small text-muted"><?= e($o['customer_email']) ?><br><?= e($o['customer_phone']) ?></td>
                        <td class="small"><?= e($o['method_name'] ?? '—') ?></td>
                        <td class="text-end fw-semibold"><?= currency($o['total_amount']) ?></td>
                        <td><span class="badge bg-<?= $badgeMap[$o['order_status']] ?? 'secondary' ?>"><?= e($o['order_status']) ?></span></td>
                        <td class="small text-muted"><?= e(date('d M Y, h:i A', strtotime($o['created_at']))) ?></td>
                        <td class="text-end">
                            <a href="order-detail.php?id=<?= (int) $o['order_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-list-ul me-1"></i>Details</a>
                            <a href="../invoice.php?order=<?= (int) $o['order_id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Invoice"><i class="bi bi-printer"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$orders): ?><tr><td colspan="8" class="text-center text-muted py-4">No orders found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <nav><ul class="pagination pagination-sm justify-content-center mb-0">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="orders.php?<?= e(http_build_query(array_filter(['page' => $i, 'status' => $status, 'search' => $search]))) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul></nav>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
