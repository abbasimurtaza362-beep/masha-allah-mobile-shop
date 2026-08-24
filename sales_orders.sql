USE masha_allah_shop;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(40) NOT NULL UNIQUE,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    customer_email VARCHAR(190) NULL,
    status ENUM('new','confirmed','processing','ready','completed','cancelled') NOT NULL DEFAULT 'new',
    payment_status ENUM('unpaid','partial','paid','refunded') NOT NULL DEFAULT 'unpaid',
    payment_method ENUM('cash','easypaisa','jazzcash','bank_transfer','other') NOT NULL DEFAULT 'cash',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    note TEXT NULL,
    stock_applied TINYINT(1) NOT NULL DEFAULT 0,
    admin_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_admin FOREIGN KEY(admin_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_status_created(status,created_at),
    INDEX idx_order_payment_created(payment_status,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(140) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_item_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_item_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_order_item_order(order_id),
    INDEX idx_order_item_product(product_id)
) ENGINE=InnoDB;
