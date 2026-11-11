CREATE TABLE IF NOT EXISTS user_access_overrides (
    user_id INT UNSIGNED PRIMARY KEY,
    mode ENUM('granted', 'blocked') NOT NULL,
    access_until DATETIME NULL,
    updated_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_access_override_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_access_override_admin FOREIGN KEY (updated_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_promotions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    promotional_price DECIMAL(10, 2) NOT NULL,
    expires_at DATETIME NOT NULL,
    status ENUM('available', 'reserved', 'redeemed') NOT NULL DEFAULT 'available',
    reserved_payment_id BIGINT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    redeemed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_promotions_user_status_expiry (user_id, status, expires_at),
    CONSTRAINT fk_user_promotion_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_promotion_plan FOREIGN KEY (plan_id) REFERENCES plans (id),
    CONSTRAINT fk_user_promotion_admin FOREIGN KEY (created_by) REFERENCES users (id),
    CONSTRAINT fk_user_promotion_payment FOREIGN KEY (reserved_payment_id) REFERENCES payments (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE payments
    ADD COLUMN promotion_id BIGINT UNSIGNED NULL,
    ADD COLUMN checkout_url VARCHAR(1000) NULL,
    ADD CONSTRAINT fk_payments_promotion FOREIGN KEY (promotion_id) REFERENCES user_promotions (id);
