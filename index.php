<?php
require_once __DIR__ . '/config.php';

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    validate_csrf();

    $productId = max(0, (int) ($_POST['product_id'] ?? 0));
    $quantity = min(99, max(1, (int) ($_POST['quantity'] ?? 1)));
    $stmt = db()->prepare('SELECT id, stock FROM products WHERE id = ? AND stock > 0 LIMIT 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if ($product) {
        $_SESSION['cart'][$productId] = min((int) $product['stock'], (int) ($_SESSION['cart'][$productId] ?? 0) + $quantity);
        add_flash('Товар добавлен в корзину.');
    } else {
        add_flash('Товар не найден или закончился.', 'error');
    }

    redirect('index.php');
}

$selectedRegion = trim((string) ($_GET['region'] ?? ''));
$selectedCategory = (int) ($_GET['category'] ?? 0);
$search = trim((string) ($_GET['search'] ?? ''));

$regions = db()->query('SELECT DISTINCT region FROM farmers ORDER BY region')->fetchAll();
$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$where = ['p.stock > 0'];
$params = [];

if ($selectedRegion !== '') {
    $where[] = 'f.region = ?';
    $params[] = $selectedRegion;
}

if ($selectedCategory > 0) {
    $where[] = 'c.id = ?';
    $params[] = $selectedCategory;
}

if ($search !== '') {
    $where[] = '(p.title LIKE ? OR p.description LIKE ? OR f.name LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = 'SELECT p.*, f.name AS farmer_name, f.region, c.name AS category_name
        FROM products p
        JOIN farmers f ON f.id = p.farmer_id
        JOIN categories c ON c.id = p.category_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY p.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Фермерская лавка</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
    <a class="logo" href="index.php">Фермерская лавка</a>
    <nav class="nav">
        <a href="index.php">Каталог</a>
        <a href="about.html">О магазине</a>
        <a href="cart.php">Корзина <span class="pill"><?= cart_count() ?></span></a>
        <?php if ($user): ?>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="admin.php">Админ-панель</a>
            <?php endif; ?>
            <form action="auth.php" method="post" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="link-button">Выйти</button>
            </form>
        <?php else: ?>
            <a href="auth.php">Войти</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <p class="eyebrow">Свежие продукты напрямую от хозяйств</p>
            <h1>Фермерские продукты с доставкой по региону</h1>
            <p>Выбирайте натуральные овощи, молочные продукты, мясо, мед и домашние заготовки от проверенных фермеров. Главная фишка магазина — фильтр по региону производства.</p>
            <div class="hero-actions">
                <a class="button primary" href="#catalog">Перейти к товарам</a>
                <a class="button ghost" href="cart.php">Открыть корзину</a>
            </div>
        </div>
        <div class="hero-card">
            <span>Сегодня в продаже</span>
            <strong><?= count($products) ?> товаров</strong>
            <small>Склад обновляется после каждого заказа</small>
        </div>
    </section>

    <?php foreach (flash_messages() as $message): ?>
        <div class="flash <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
    <?php endforeach; ?>

    <section class="filters" id="catalog">
        <form method="get" class="filter-form">
            <label>
                Поиск
                <input type="search" name="search" value="<?= e($search) ?>" placeholder="Молоко, мед, фермер">
            </label>
            <label>
                Регион производства
                <select name="region">
                    <option value="">Все регионы</option>
                    <?php foreach ($regions as $region): ?>
                        <option value="<?= e($region['region']) ?>" <?= $selectedRegion === $region['region'] ? 'selected' : '' ?>><?= e($region['region']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Категория
                <select name="category">
                    <option value="0">Все категории</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= $selectedCategory === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="button primary">Показать</button>
            <a class="button ghost" href="index.php">Сбросить</a>
        </form>
    </section>

    <section class="product-grid">
        <?php if (!$products): ?>
            <div class="empty">По выбранным фильтрам товары не найдены.</div>
        <?php endif; ?>

        <?php foreach ($products as $product): ?>
            <article class="product-card">
                <div class="product-visual">
                    <span><?= e(mb_substr($product['category_name'], 0, 1, 'UTF-8')) ?></span>
                </div>
                <div class="product-body">
                    <div class="product-meta">
                        <span><?= e($product['category_name']) ?></span>
                        <span><?= e($product['region']) ?></span>
                    </div>
                    <h2><?= e($product['title']) ?></h2>
                    <p><?= e($product['description']) ?></p>
                    <div class="farmer">Фермер: <?= e($product['farmer_name']) ?></div>
                    <div class="product-footer">
                        <strong><?= number_format((float) $product['price'], 0, ',', ' ') ?> ₽ / <?= e($product['unit']) ?></strong>
                        <span>В наличии: <?= (int) $product['stock'] ?></span>
                    </div>
                    <form method="post" class="cart-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="add_to_cart">
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <input type="number" name="quantity" min="1" max="<?= (int) $product['stock'] ?>" value="1" aria-label="Количество">
                        <button type="submit" class="button primary">В корзину</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<footer class="footer">
    <p>Фермерская лавка — темно-зеленый интернет-магазин натуральных продуктов.</p>
</footer>
<script src="script.js"></script>
</body>
</html>