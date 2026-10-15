</main>
<footer class="bg-dark text-light pt-5 pb-4 mt-5">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-4">
                <h5 class="fw-bold"><i class="bi bi-bag-fill me-1"></i><?= e($config['app_name']) ?></h5>
                <p class="text-secondary small">Quality products at honest prices. Shop electronics, fashion, home goods and more.</p>
            </div>
            <div class="col-md-2">
                <h6>Shop</h6>
                <ul class="list-unstyled small">
                    <li><a class="link-secondary text-decoration-none" href="shop.php">All Products</a></li>
                    <li><a class="link-secondary text-decoration-none" href="cart.php">Cart</a></li>
                    <li><a class="link-secondary text-decoration-none" href="checkout.php">Checkout</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6>Company</h6>
                <ul class="list-unstyled small">
                    <li><a class="link-secondary text-decoration-none" href="about.php">About Us</a></li>
                    <li><a class="link-secondary text-decoration-none" href="contact.php">Contact Us</a></li>
                    <li><a class="link-secondary text-decoration-none" href="admin/login.php">Admin Panel</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6>Contact</h6>
                <ul class="list-unstyled small text-secondary">
                    <li><i class="bi bi-geo-alt me-1"></i>No. 45, Yangon, Myanmar</li>
                    <li><i class="bi bi-telephone me-1"></i>+95 9 123 456 789</li>
                    <li><i class="bi bi-envelope me-1"></i>support@example.com</li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <p class="text-center text-secondary small mb-0">&copy; <?= date('Y') ?> <?= e($config['app_name']) ?>. All rights reserved.</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
