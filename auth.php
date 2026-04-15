<?php
require_once __DIR__ . '/config.php';

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        redirect('index.php');
    }

    if ($action === 'register') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');
        $password = (string) ($_POST['password'] ?? '');

        if (mb_strlen($name, 'UTF-8') < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password, 'UTF-8') < 6) {
            add_flash('Проверьте имя, email и пароль. Пароль должен быть не короче 6 символов.', 'error');
            redirect('auth.php');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "user")');
            $stmt->execute([$name, $email, $hash]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) db()->lastInsertId();
            add_flash('Регистрация выполнена. Добро пожаловать!');
            redirect('index.php');
        } catch (PDOException $error) {
            add_flash('Пользователь с таким email уже существует.', 'error');
            redirect('auth.php');
        }
    }

    if ($action === 'login') {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');
        $password = (string) ($_POST['password'] ?? '');

        $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $foundUser = $stmt->fetch();

        if ($foundUser && password_verify($password, $foundUser['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $foundUser['id'];
            add_flash('Вы вошли в аккаунт.');
            redirect('index.php');
        }

        add_flash('Неверный email или пароль.', 'error');
        redirect('auth.php');
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход и регистрация</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
    <a class="logo" href="index.php">Фермерская лавка</a>
    <nav class="nav">
        <a href="index.php">Каталог</a>
        <a href="cart.php">Корзина <span class="pill"><?= cart_count() ?></span></a>
    </nav>
</header>

<main class="auth-page">
    <?php foreach (flash_messages() as $message): ?>
        <div class="flash <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
    <?php endforeach; ?>

    <?php if ($user): ?>
        <section class="panel narrow">
            <h1>Вы уже вошли</h1>
            <p>Аккаунт: <?= e($user['name']) ?>, <?= e($user['email']) ?></p>
            <a class="button primary" href="index.php">Вернуться в магазин</a>
        </section>
    <?php else: ?>
        <section class="auth-grid">
            <form method="post" class="panel form-card">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="login">
                <p class="eyebrow">Для покупателей</p>
                <h1>Вход</h1>
                <label>Email<input type="email" name="email" required autocomplete="email"></label>
                <label>Пароль<input type="password" name="password" required autocomplete="current-password"></label>
                <button class="button primary" type="submit">Войти</button>
                <p class="hint">Админ для проверки: admin@farm.local, пароль: password</p>
            </form>

            <form method="post" class="panel form-card">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="register">
                <p class="eyebrow">Новый аккаунт</p>
                <h1>Регистрация</h1>
                <label>Имя<input type="text" name="name" minlength="2" required autocomplete="name"></label>
                <label>Email<input type="email" name="email" required autocomplete="email"></label>
                <label>Пароль<input type="password" name="password" minlength="6" required autocomplete="new-password"></label>
                <button class="button primary" type="submit">Создать аккаунт</button>
            </form>
        </section>
    <?php endif; ?>
</main>
<script src="script.js"></script>
</body>
</html>