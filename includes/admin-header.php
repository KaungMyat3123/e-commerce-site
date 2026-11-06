<?php
// includes/admin-header.php — admin panel layout header (expects $adminTitle, $adminActive)
require_once __DIR__ . '/auth.php';

$base = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($adminTitle ?? 'Admin') ?> &middot; Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base ?>assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
<div class="d-flex">
    <aside class="admin-sidebar text-white">
        <div class="p-3 border-bottom border-secondary">
            <span class="fw-bold fs-5"><i class="bi bi-bag-fill me-1"></i>Admin Panel</span>
        </div>
        <nav class="nav flex-column p-2">
            <a class="nav-link <?= ($adminActive ?? '') === 'dashboard' ? 'active' : '' ?>" href="index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
            <a class="nav-link <?= ($adminActive ?? '') === 'categories' ? 'active' : '' ?>" href="categories.php"><i class="bi bi-tags me-2"></i>Categories</a>
            <a class="nav-link <?= ($adminActive ?? '') === 'products' ? 'active' : '' ?>" href="products.php"><i class="bi bi-box-seam me-2"></i>Products</a>
            <a class="nav-link <?= ($adminActive ?? '') === 'orders' ? 'active' : '' ?>" href="orders.php"><i class="bi bi-receipt me-2"></i>Orders</a>
            <a class="nav-link <?= ($adminActive ?? '') === 'payments' ? 'active' : '' ?>" href="payments.php"><i class="bi bi-credit-card me-2"></i>Payment Methods</a>
            <a class="nav-link <?= ($adminActive ?? '') === 'inquiries' ? 'active' : '' ?>" href="inquiries.php"><i class="bi bi-envelope me-2"></i>Inquiries</a>
            <a class="nav-link <?= ($adminActive ?? '') === 'reviews' ? 'active' : '' ?>" href="reviews.php"><i class="bi bi-star me-2"></i>Reviews</a>
            <a class="nav-link" href="<?= $base ?>index.php" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i>View Storefront</a>
        </nav>
    </aside>
    <div class="flex-grow-1 admin-content">
        <header class="d-flex justify-content-between align-items-center bg-white border-bottom px-4 py-2">
            <span class="fw-semibold"><?= e($adminTitle ?? '') ?></span>
            <span class="text-muted small">
                <i class="bi bi-person-circle me-1"></i><?= e($_SESSION['admin_username'] ?? 'admin') ?>
                <a href="logout.php" class="btn btn-sm btn-outline-danger ms-3">Logout</a>
            </span>
        </header>
        <div class="p-4">
            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
