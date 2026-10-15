<?php
// about.php — About Us
require_once __DIR__ . '/includes/db.php';

$pageTitle  = 'About Us';
$activePage = 'about';
require __DIR__ . '/includes/header.php';
?>

<section class="bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h1 class="fw-bold">About <?= e($config['app_name']) ?></h1>
                <p class="lead text-muted">Your trusted online shopping destination since 2020.</p>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="row gy-4">
        <div class="col-md-6">
            <h2 class="h4 fw-bold"><i class="bi bi-bullseye text-primary me-2"></i>Our Mission</h2>
            <p class="text-muted">
                To make quality products accessible to everyone by offering a curated catalog, transparent pricing,
                and a shopping experience that is fast, secure, and delightful — from browsing to checkout and delivery.
            </p>
        </div>
        <div class="col-md-6">
            <h2 class="h4 fw-bold"><i class="bi bi-eye text-primary me-2"></i>Our Vision</h2>
            <p class="text-muted">
                To become the region's most loved e-commerce platform, known for trustworthy service,
                exceptional product quality, and technology that brings great value to every customer.
            </p>
        </div>
    </div>

    <hr class="my-5">

    <div class="row gy-4 text-center">
        <div class="col-md-3">
            <h3 class="h2 fw-bold text-primary">4+</h3>
            <p class="text-muted">Years in business</p>
        </div>
        <div class="col-md-3">
            <h3 class="h2 fw-bold text-primary">10k+</h3>
            <p class="text-muted">Happy customers</p>
        </div>
        <div class="col-md-3">
            <h3 class="h2 fw-bold text-primary">500+</h3>
            <p class="text-muted">Products in catalog</p>
        </div>
        <div class="col-md-3">
            <h3 class="h2 fw-bold text-primary">24/7</h3>
            <p class="text-muted">Customer support</p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
