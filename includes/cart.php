<?php
// includes/cart.php — session-based shopping cart helpers

require_once __DIR__ . '/db.php';

function cart_raw(): array
{
    return $_SESSION['cart'] ?? []; // [product_id => qty]
}

function cart_add(int $productId, int $qty = 1): void
{
    $current = $_SESSION['cart'][$productId] ?? 0;
    $_SESSION['cart'][$productId] = max(1, $current + $qty);
}

function cart_update(int $productId, int $qty): void
{
    if ($qty <= 0) {
        cart_remove($productId);
        return;
    }
    $_SESSION['cart'][$productId] = $qty;
}

function cart_remove(int $productId): void
{
    unset($_SESSION['cart'][$productId]);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

/** Cart lines hydrated with product data; drops products that no longer exist. */
function cart_items(PDO $conn): array
{
    $raw = cart_raw();
    if (!$raw) {
        return [];
    }

    $ids = array_map('intval', array_keys($raw));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT product_id, name, price, stock, image_url FROM Products WHERE product_id IN ($placeholders)");
    $stmt->execute($ids);

    $items = [];
    $clean = [];
    foreach ($stmt->fetchAll() as $product) {
        $id = (int) $product['product_id'];
        $qty = min((int) $raw[$id], max(1, (int) $product['stock']));
        $product['quantity'] = $qty;
        $product['line_total'] = (float) $product['price'] * $qty;
        $items[] = $product;
        $clean[$id] = $qty;
    }
    $_SESSION['cart'] = $clean;

    return $items;
}

function cart_count(): int
{
    return array_sum(cart_raw());
}

function cart_total(PDO $conn): float
{
    return array_sum(array_column(cart_items($conn), 'line_total'));
}
