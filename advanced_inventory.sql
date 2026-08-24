USE masha_allah_shop;

ALTER TABLE products ADD COLUMN IF NOT EXISTS reorder_level INT NOT NULL DEFAULT 5 AFTER quantity;

CREATE TABLE IF NOT EXISTS inventory_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(140) NOT NULL,
    quantity_before INT NOT NULL,
    quantity_change INT NOT NULL,
    quantity_after INT NOT NULL,
    movement_type VARCHAR(40) NOT NULL,
    note VARCHAR(255) NULL,
    admin_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_movement_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL,
    CONSTRAINT fk_inventory_movement_admin FOREIGN KEY(admin_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_inventory_movement_product_created(product_id,created_at),
    INDEX idx_inventory_movement_created(created_at)
) ENGINE=InnoDB;
