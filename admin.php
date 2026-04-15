<?php
require_once __DIR__ . '/config.php';

$user = require_login();

if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Доступ только для администратора.');
}

$allowedStatuses = ['new', 'processing', 'delivered', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $orderId = max(0, (int) ($_POST['order_id'] ?? 0));
    $status = (string) ($_POST['status'] ?? '');

    if ($orderId > 0 && in_array($status, $allowedStatuses, true)) {
        $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $orderId]);
        add_flash('Статус заказа обновлен.');
    }

    redirect('admin.php');
}

$orders = db()->query('SELECT o.*, u.email AS user_email FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC')->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
    <a class="logo" href="index.php">Фермерская лавка</a>
    <nav class="nav">
        <a href="index.php">Каталог</a>
        <a href="cart.php">Корзина <span class="pill"><?= cart_count() ?></span></a>
        <form action="auth.php" method="post" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="link-button">Выйти</button>
        </form>
    </nav>
</header>

<main>
    <?php foreach (flash_messages() as $message): ?>
        <div class="flash <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
    <?php endforeach; ?>

    <section class="page-title">
        <p class="eyebrow">Заявки на доставку</p>
        <h1>Панель администратора</h1>
    </section>

    <section class="admin-list">
        <?php if (!$orders): ?>
            <div class="panel empty">Пока нет заказов.</div>
        <?php endif; ?>

        <?php foreach ($orders as $order): ?>
            <?php
            $stmt = db()->prepare('SELECT oi.quantity, oi.price, p.title FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
            $stmt->execute([(int) $order['id']]);
            $items = $stmt->fetchAll();
            ?>
            <article class="panel order-card">
                <div class="order-head">
                    <div>
                        <span class="pill">Заказ #<?= (int) $order['id'] ?></span>
                        <h2><?= e($order['customer_name']) ?></h2>
                        <p><?= e($order['phone']) ?> · <?= e($order['user_email']) ?></p>
                    </div>
                    <strong><?= number_format((float) $order['total'], 0, ',', ' ') ?> ₽</strong>
                </div>
                <div class="order-details">
                    <p><b>Адрес:</b> <?= e($order['address']) ?></p>
                    <p><b>Регион доставки:</b> <?= e($order['delivery_region']) ?></p>
                    <p><b>Создан:</b> <?= e($order['created_at']) ?></p>
                </div>
                <div class="items-list">
                    <?php foreach ($items as $item): ?>
                        <div>
                            <span><?= e($item['title']) ?></span>
                            <span><?= (int) $item['quantity'] ?> × <?= number_format((float) $item['price'], 0, ',', ' ') ?> ₽</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="post" class="status-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                    <label>
                        Статус
                        <select name="status">
                            <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>>Новая</option>
                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>В обработке</option>
                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Доставлена</option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Отменена</option>
                        </select>
                    </label>
                    <button type="submit" class="button primary">Сохранить</button>
                </form>
            </article>
        <?php endforeach; ?>
    </section>
</main>
<script src="script.js"></script>
</body>
</html>