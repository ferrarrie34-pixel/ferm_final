<?php
require_once __DIR__ . '/config.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cart.php');
}

validate_csrf();

$cart = $_SESSION['cart'] ?? [];

if (!$cart) {
    add_flash('Корзина пустая.', 'error');
    redirect('cart.php');
}

$customerName = trim((string) ($_POST['customer_name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$deliveryRegion = trim((string) ($_POST['delivery_region'] ?? ''));

if ($customerName === '' || $phone === '' || $address === '' || $deliveryRegion === '') {
    add_flash('Заполните все поля доставки.', 'error');
    redirect('cart.php');
}

$ids = array_map('intval', array_keys($cart));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare("SELECT id, title, price, stock FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$products = $stmt->fetchAll();

if (!$products) {
    add_flash('Товары из корзины больше недоступны.', 'error');
    redirect('cart.php');
}

$subtotal = 0.0;
$items = [];

foreach ($products as $product) {
    $quantity = min((int) $cart[(int) $product['id']], (int) $product['stock']);

    if ($quantity <= 0) {
        continue;
    }

    $lineTotal = (float) $product['price'] * $quantity;
    $subtotal += $lineTotal;
    $items[] = ['product' => $product, 'quantity' => $quantity, 'line_total' => $lineTotal];
}

if (!$items) {
    add_flash('Товары закончились на складе.', 'error');
    redirect('cart.php');
}

$deliveryCost = delivery_cost($deliveryRegion);
$total = $subtotal + $deliveryCost;

$pdo = db();
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare('INSERT INTO orders (user_id, customer_name, phone, address, delivery_region, subtotal, delivery_cost, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "new")');
    $stmt->execute([(int) $user['id'], $customerName, $phone, $address, $deliveryRegion, $subtotal, $deliveryCost, $total]);
    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
    $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

    foreach ($items as $item) {
        $product = $item['product'];
        $quantity = (int) $item['quantity'];
        $stockStmt->execute([$quantity, (int) $product['id'], $quantity]);

        if ($stockStmt->rowCount() !== 1) {
            throw new RuntimeException('Недостаточно товара на складе.');
        }

        $itemStmt->execute([$orderId, (int) $product['id'], $quantity, (float) $product['price']]);
    }

    $pdo->commit();
    unset($_SESSION['cart']);
    add_flash('Заказ оформлен. Заявка на доставку появилась в админ-панели.');
    redirect('index.php');
} catch (Throwable $error) {
    $pdo->rollBack();
    add_flash('Не удалось оформить заказ. Попробуйте еще раз.', 'error');
    redirect('cart.php');
}