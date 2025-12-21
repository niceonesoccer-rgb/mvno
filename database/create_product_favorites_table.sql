USE mvno_db;

CREATE TABLE IF NOT EXISTS product_favorites (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT(11) UNSIGNED NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    product_type ENUM('mvno', 'mno', 'internet') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_product_user (product_id, user_id),
    KEY idx_user_id (user_id),
    KEY idx_product_type (product_type),
    KEY idx_created_at (created_at),
    CONSTRAINT fk_favorite_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;












