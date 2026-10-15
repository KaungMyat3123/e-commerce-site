<?php
// admin/index.php — dashboard & analytics
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$stats = $conn->query(
    "SELECT
        (SELECT COUNT(*) FROM Orders) AS total_orders,
        (SELECT IFNULL(SUM(total_amount), 0) FROM Orders WHERE order_status <> 'Cancelled') AS total_sales,
        (SELECT COUNT(DISTINCT customer_email) FROM Orders) AS total_customers,
        (SELECT COUNT(*) FROM Products) AS total_products,
        (SELECT COUNT(*) FROM Products WHERE stock <= 5) AS low_stock,
        (SELECT COUNT(*) FROM ContactInquiries) AS total_inquiries"
)->fetch();

$statusCounts = $conn->query(
    "SELECT order_status, COUNT(*) AS cnt FROM Orders GROUP BY order_status"
)->fetchAll();

$recentOrders = $conn->query(
    'SELECT order_id, customer_name, total_amount, order_status, created_at
     FROM Orders ORDER BY created_at DESC, order_id DESC LIMIT 5'
)->fetchAll();

$recentInquiries = $conn->query(
    'SELECT inquiry_id, name, email, message, created_at
     FROM ContactInquiries ORDER BY created_at DESC, inquiry_id DESC LIMIT 5'
)->fetchAll();

$adminTitle = 'Dashboard';
$adminActive = 'dashboard';
require __DIR__ . '/../includes/admin-header.php';

$badgeMap = ['Pending' => 'warning', 'Confirmed' => 'success', 'Shipped' => 'primary', 'Cancelled' => 'secondary'];
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card text-bg-primary h-100"><div class="card-body">
            <div class="d-flex justify-content-between"><span>Total Sales</span><i class="bi bi-currency-dollar fs-4"></i></div>
            <h3 class="mt-2 mb-0"><?= currency($stats['total_sales']) ?></h3>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card text-bg-success h-100"><div class="card-body">
            <div class="d-flex justify-content-between"><span>Total Orders</span><i class="bi bi-receipt fs-4"></i></div>
            <h3 class="mt-2 mb-0"><?= (int) $stats['total_orders'] ?></h3>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card text-bg-info h-100"><div class="card-body">
            <div class="d-flex justify-content-between"><span>Customers</span><i class="bi bi-people fs-4"></i></div>
            <h3 class="mt-2 mb-0"><?= (int) $stats['total_customers'] ?></h3>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card text-bg-warning h-100"><div class="card-body">
            <div class="d-flex justify-content-between"><span>Inquiries</span><i class="bi bi-envelope fs-4"></i></div>
            <h3 class="mt-2 mb-0"><?= (int) $stats['total_inquiries'] ?></h3>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h6 class="fw-bold mb-3">Catalog</h6>
            <p class="mb-1"><i class="bi bi-box-seam me-2 text-primary"></i>Products: <strong><?= (int) $stats['total_products'] ?></strong></p>
            <p class="mb-0"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Low stock (&le; 5): <strong><?= (int) $stats['low_stock'] ?></strong>
                <a href="products.php" class="small ms-2">Manage stock &rarr;</a></p>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h6 class="fw-bold mb-3">Orders by Status</h6>
            <?php if ($statusCounts): ?>
                <?php foreach ($statusCounts as $row): ?>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="badge bg-<?= $badgeMap[$row['order_status']] ?? 'secondary' ?>"><?= e($row['order_status']) ?></span>
                        <span><?= (int) $row['cnt'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted small mb-0">No orders yet.</p>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header fw-bold d-flex justify-content-between">Recent Orders <a href="orders.php" class="small">View all</a></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th>#</th><th>Customer</th><th class="text-end">Total</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td><a href="order-detail.php?id=<?= (int) $o['order_id'] ?>">#<?= (int) $o['order_id'] ?></a></td>
                                <td><?= e($o['customer_name']) ?></td>
                                <td class="text-end"><?= currency($o['total_amount']) ?></td>
                                <td><span class="badge bg-<?= $badgeMap[$o['order_status']] ?? 'secondary' ?>"><?= e($o['order_status']) ?></span></td>
                                <td class="small text-muted"><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$recentOrders): ?><tr><td colspan="5" class="text-center text-muted py-4">No orders yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header fw-bold d-flex justify-content-between">Recent Inquiries <a href="inquiries.php" class="small">View all</a></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentInquiries as $in): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between"><strong class="small"><?= e($in['name']) ?></strong><span class="small text-muted"><?= e(date('d M', strtotime($in['created_at']))) ?></span></div>
                        <div class="small text-muted text-truncate"><?= e($in['message']) ?></div>
                    </li>
                <?php endforeach; ?>
                <?php if (!$recentInquiries): ?><li class="list-group-item text-center text-muted py-4">No inquiries yet.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
