<?php
// admin/reviews.php — product review moderation
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $reviewId = (int) ($_POST['review_id'] ?? 0);
    $action = $_POST['post_action'] ?? '';

    if ($action === 'approve') {
        $conn->prepare("UPDATE Reviews SET status = 'approved' WHERE review_id = ?")->execute([$reviewId]);
        flash('success', 'Review approved and published.');
    } elseif ($action === 'decline') {
        $conn->prepare("UPDATE Reviews SET status = 'declined' WHERE review_id = ?")->execute([$reviewId]);
        flash('success', 'Review declined.');
    } elseif ($action === 'delete') {
        $conn->prepare('DELETE FROM Reviews WHERE review_id = ?')->execute([$reviewId]);
        flash('success', 'Review deleted.');
    }
    header('Location: reviews.php');
    exit;
}

$reviews = $conn->query(
    "SELECT r.*, p.name AS product_name
     FROM Reviews r LEFT JOIN Products p ON p.product_id = r.product_id
     ORDER BY (r.status = 'pending') DESC, r.created_at DESC, r.review_id DESC"
)->fetchAll();

$adminTitle = 'Review Management';
$adminActive = 'reviews';
require __DIR__ . '/../includes/admin-header.php';
?>

<div class="card">
    <div class="card-header fw-bold">Product Reviews (<?= count($reviews) ?>)</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>ID</th><th>Product</th><th>Reviewer</th><th>Review</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                    <tr>
                        <td><?= (int) $r['review_id'] ?></td>
                        <td class="small fw-semibold">
                            <a href="<?= $base ?>product-detail.php?id=<?= (int) $r['product_id'] ?>" target="_blank"><?= e($r['product_name'] ?? 'Deleted product') ?></a>
                        </td>
                        <td class="small"><?= e($r['reviewer_name']) ?></td>
                        <td class="small text-muted" style="max-width:340px"><?= nl2br(e($r['comment'])) ?></td>
                        <td>
                            <?php if ($r['status'] === 'approved'): ?>
                                <span class="badge bg-success">Approved</span>
                            <?php elseif ($r['status'] === 'declined'): ?>
                                <span class="badge bg-danger">Declined</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= e(date('d M Y, h:i A', strtotime($r['created_at']))) ?></td>
                        <td class="text-end text-nowrap">
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="post_action" value="approve">
                                <input type="hidden" name="review_id" value="<?= (int) $r['review_id'] ?>">
                                <button class="btn btn-sm btn-success" <?= $r['status'] === 'approved' ? 'disabled' : '' ?>><i class="bi bi-check-lg me-1"></i>Approve</button>
                            </form>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="post_action" value="decline">
                                <input type="hidden" name="review_id" value="<?= (int) $r['review_id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" <?= $r['status'] === 'declined' ? 'disabled' : '' ?>><i class="bi bi-x-lg me-1"></i>Cancel</button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this review?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="post_action" value="delete">
                                <input type="hidden" name="review_id" value="<?= (int) $r['review_id'] ?>">
                                <button class="btn btn-sm btn-outline-secondary" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$reviews): ?><tr><td colspan="7" class="text-center text-muted py-4">No reviews yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
