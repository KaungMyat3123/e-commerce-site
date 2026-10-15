<?php
// admin/inquiries.php — contact message logs
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['post_action'] ?? '') === 'delete') {
        $conn->prepare('DELETE FROM ContactInquiries WHERE inquiry_id = ?')->execute([(int) $_POST['inquiry_id']]);
        flash('success', 'Inquiry deleted.');
        header('Location: inquiries.php');
        exit;
    }
}

$inquiries = $conn->query('SELECT * FROM ContactInquiries ORDER BY created_at DESC, inquiry_id DESC')->fetchAll();

$adminTitle = 'Inquiry Management';
$adminActive = 'inquiries';
require __DIR__ . '/../includes/admin-header.php';
?>

<div class="card">
    <div class="card-header fw-bold">Contact Inquiries (<?= count($inquiries) ?>)</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($inquiries as $in): ?>
                    <tr>
                        <td><?= (int) $in['inquiry_id'] ?></td>
                        <td class="fw-semibold"><?= e($in['name']) ?></td>
                        <td class="small"><a href="mailto:<?= e($in['email']) ?>"><?= e($in['email']) ?></a></td>
                        <td class="small text-muted" style="max-width:380px"><?= nl2br(e($in['message'])) ?></td>
                        <td class="small text-muted"><?= e(date('d M Y, h:i A', strtotime($in['created_at']))) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="mailto:<?= e($in['email']) ?>?subject=<?= rawurlencode('Re: your inquiry - ' . $config['app_name']) ?>" title="Respond"><i class="bi bi-reply"></i></a>
                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this inquiry?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="post_action" value="delete">
                                <input type="hidden" name="inquiry_id" value="<?= (int) $in['inquiry_id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$inquiries): ?><tr><td colspan="6" class="text-center text-muted py-4">No inquiries yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
