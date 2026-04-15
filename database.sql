CREATE DATABASE IF NOT EXISTS farmer_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE farmer_shop;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS farmers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    region VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    unit VARCHAR(40) NOT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_farmer FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_products_price (price),
    INDEX idx_products_stock (stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    customer_name VARCHAR(120) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    address VARCHAR(500) NOT NULL,
    delivery_region VARCHAR(120) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    delivery_cost DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('new', 'processing', 'delivered', 'cancelled') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_orders_status (status),
    INDEX idx_orders_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, password_hash, role) VALUES
('Администратор', 'admin@farm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role);

INSERT INTO categories (name) VALUES
('Овощи'),
('Молочные продукты'),
('Мясо и птица'),
('Мед и варенье'),
('Зелень и травы')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO farmers (name, region, description) VALUES
('Хутор Зеленая долина', 'Краснодарский край', 'Семейное хозяйство с овощами открытого грунта и домашними заготовками.'),
('Ферма Луговая', 'Республика Адыгея', 'Небольшая молочная ферма с натуральными сырами, творогом и молоком.'),
('Донские поля', 'Ростовская область', 'Фермерское хозяйство с мясом птицы, яйцами и сезонными овощами.'),
('Пасека Солнечная', 'Ставропольский край', 'Пасека с цветочным медом, липовым медом и ягодным вареньем.');

INSERT INTO products (farmer_id, category_id, title, description, price, unit, stock) VALUES
(1, 1, 'Томаты грунтовые', 'Сладкие мясистые томаты свежего сбора без лишней химии.', 220.00, 'кг', 40),
(1, 1, 'Огурцы хрустящие', 'Огурцы с фермерской грядки для салатов и засолки.', 160.00, 'кг', 55),
(2, 2, 'Молоко цельное', 'Свежое пастеризованное молоко с насыщенным сливочным вкусом.', 120.00, 'л', 35),
(2, 2, 'Сыр домашний', 'Плотный фермерский сыр для завтраков, салатов и горячих блюд.', 640.00, 'кг', 18),
(3, 3, 'Курица фермерская', 'Охлажденная птица свободного выгула.', 390.00, 'кг', 22),
(3, 1, 'Картофель молодой', 'Ровный молодой картофель из Ростовской области.', 95.00, 'кг', 80),
(4, 4, 'Мед цветочный', 'Ароматный мед с разнотравья, собранный на пасеке.', 520.00, 'банка', 30),
(4, 4, 'Варенье из инжира', 'Домашнее варенье небольшими партиями.', 360.00, 'банка', 24),
(1, 5, 'Петрушка и укроп', 'Свежая зелень, срезанная утром перед отправкой.', 85.00, 'пучок', 60);