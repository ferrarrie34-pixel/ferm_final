<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $productId = max(0, (int) ($_POST['product_id'] ?? 0));

    if ($action === 'update' && $productId > 0) {
        $quantity = min(99, max(1, (int) ($_POST['quantity'] ?? 1)));
        $_SESSION['cart'][$productId] = $quantity;
        add_flash('Корзина обновлена.');
    }

    if ($action === 'remove' && $productId > 0) {
        unset($_SESSION['cart'][$productId]);
        add_flash('Товар удален из корзины.');
    }

    redirect('cart.php');
}

$cart = $_SESSION['cart'] ?? [];
$items = [];
$subtotal = 0.0;

if ($cart) {
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT p.*, f.name AS farmer_name, f.region FROM products p JOIN farmers f ON f.id = p.farmer_id WHERE p.id IN ($placeholders)");
    $stmt->execute($ids);

    foreach ($stmt->fetchAll() as $product) {
        $quantity = min((int) $cart[(int) $product['id']], (int) $product['stock']);
        $lineTotal = (float) $product['price'] * $quantity;
        $subtotal += $lineTotal;
        $items[] = ['product' => $product, 'quantity' => $quantity, 'line_total' => $lineTotal];
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Корзина</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
    <a class="logo" href="index.php">Фермерская лавка</a>
    <nav class="nav">
        <a href="index.php">Каталог</a>
        <a href="auth.php">Аккаунт</a>
    </nav>
</header>

<main>
    <?php foreach (flash_messages() as $message): ?>
        <div class="flash <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
    <?php endforeach; ?>

    <section class="page-title">
        <p class="eyebrow">Проверьте заказ</p>
        <h1>Корзина и доставка</h1>
    </section>

    <?php if (!$items): ?>
        <div class="panel empty">
            <h2>Корзина пустая</h2>
            <p>Добавьте продукты из каталога, чтобы оформить доставку.</p>
            <a class="button primary" href="index.php">В каталог</a>
        </div>
    <?php else: ?>
        <section class="cart-layout">
            <div class="panel">
                <?php foreach ($items as $item): ?>
                    <?php $product = $item['product']; ?>
                    <article class="cart-row">
                        <div>
                            <strong><?= e($product['title']) ?></strong>
                            <span><?= e($product['farmer_name']) ?>, <?= e($product['region']) ?></span>
                        </div>
                        <div><?= number_format((float) $product['price'], 0, ',', ' ') ?> ₽ / <?= e($product['unit']) ?></div>
                        <form method="post" class="row-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                            <input type="number" name="quantity" min="1" max="<?= (int) $product['stock'] ?>" value="<?= (int) $item['quantity'] ?>">
                            <button type="submit" class="button small">Обновить</button>
                        </form>
                        <strong><?= number_format($item['line_total'], 0, ',', ' ') ?> ₽</strong>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                            <button type="submit" class="button danger">Удалить</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>

            <form method="post" action="checkout.php" class="panel checkout-card">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <h2>Оформление доставки</h2>
                <label>Ваше имя<input type="text" name="customer_name" required maxlength="120"></label>
                <label>Телефон<input type="tel" name="phone" required maxlength="40"></label>
                <label>Адрес доставки<textarea name="address" required maxlength="500"></textarea></label>
                <label>
                    Регион доставки
                    <select name="delivery_region" id="delivery-region" required>
                        <option value="Краснодарский край" data-cost="250">Краснодарский край — 250 ₽</option>
                        <option value="Республика Адыгея" data-cost="350">Республика Адыгея — 350 ₽</option>
                        <option value="Ростовская область" data-cost="450">Ростовская область — 450 ₽</option>
                        <option value="Ставропольский край" data-cost="550">Ставропольский край — 550 ₽</option>
                        <option value="Другой регион" data-cost="700">Другой регион — 700 ₽</option>
                    </select>
                </label>
                <div class="summary" data-subtotal="<?= (float) $subtotal ?>">
                    <div><span>Товары</span><strong><?= number_format($subtotal, 0, ',', ' ') ?> ₽</strong></div>
                    <div><span>Доставка</span><strong id="delivery-cost">250 ₽</strong></div>
                    <div class="summary-total"><span>Итого</span><strong id="order-total"><?= number_format($subtotal + 250, 0, ',', ' ') ?> ₽</strong></div>
                </div>
                <button type="submit" class="button primary full">Оформить заказ</button>
                <p class="hint">Для оформления нужен вход в аккаунт. Если вы не вошли, сайт перенаправит на страницу входа.</p>
            </form>
        </section>
    <?php endif; ?>
</main>
<script src="script.js"></script>
</body>
</html>