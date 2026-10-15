<?php
// includes/header.php — public site header
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cart.php';

$pageTitle   = $pageTitle ?? $config['app_name'];
$cartCount   = cart_count();
$activePage  = $activePage ?? '';
$navCats     = $conn->query('SELECT category_id, category_name FROM Categories ORDER BY category_name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> &middot; <?= e($config['app_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-bag-fill me-1"></i><?= e($config['app_name']) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'shop' ? 'active' : '' ?>" href="shop.php">Shop</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Categories</a>
                    <ul class="dropdown-menu">
                        <?php foreach ($navCats as $cat): ?>
                            <li><a class="dropdown-item" href="shop.php?category=<?= (int) $cat['category_id'] ?>"><?= e($cat['category_name']) ?></a></li>
                        <?php endforeach; ?>
                        <?php if (!$navCats): ?>
                            <li><span class="dropdown-item text-muted">No categories yet</span></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'about' ? 'active' : '' ?>" href="about.php">About Us</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'contact' ? 'active' : '' ?>" href="contact.php">Contact</a></li>
            </ul>
            <a href="cart.php" class="btn btn-outline-light position-relative">
                <i class="bi bi-cart3 me-1"></i>Cart
                <?php if ($cartCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= (int) $cartCount ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</nav>
<main>
<?php if ($msg = flash('success')): ?>
    <div class="container mt-3"><div class="alert alert-success alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div></div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
    <div class="container mt-3"><div class="alert alert-danger alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div></div>
<?php endif; ?>
