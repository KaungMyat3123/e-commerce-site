<?php
// contact.php — Contact Us form + handler
require_once __DIR__ . '/includes/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Please enter your name (max 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($message === '' || mb_strlen($message) > 4000) {
        $errors[] = 'Please enter a message (max 4000 characters).';
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            'INSERT INTO ContactInquiries (name, email, message) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $email, $message]);
        flash('success', 'Thank you! Your inquiry has been received. We will get back to you shortly.');
        header('Location: contact.php');
        exit;
    }
}

$pageTitle  = 'Contact Us';
$activePage = 'contact';
require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <div class="row justify-content-center gy-4">
        <div class="col-lg-5">
            <h1 class="fw-bold mb-4">Get in Touch</h1>
            <p class="text-muted">Have a question about an order or product? Send us a message and our team will respond as soon as possible.</p>
            <ul class="list-unstyled">
                <li class="mb-2"><i class="bi bi-geo-alt text-primary me-2"></i>No. 45, Yangon, Myanmar</li>
                <li class="mb-2"><i class="bi bi-telephone text-primary me-2"></i>+95 9 123 456 789</li>
                <li class="mb-2"><i class="bi bi-envelope text-primary me-2"></i>support@example.com</li>
                <li class="mb-2"><i class="bi bi-clock text-primary me-2"></i>Mon&ndash;Sat, 9:00 AM &ndash; 6:00 PM</li>
            </ul>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 mb-4">Send an Inquiry</h2>
                    <?php if ($errors): ?>
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <form method="post" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="name" name="name" required maxlength="100" value="<?= e($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required maxlength="4000"><?= e($_POST['message'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
