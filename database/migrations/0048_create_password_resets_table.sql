-- Recuperacao de senha por e-mail. Guarda so o HASH do token (o token em si
-- vai apenas no link enviado), vale por tempo limitado e uma unica vez.
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_password_resets_token (token_hash),
    INDEX idx_password_resets_user (user_id, created_at),
    INDEX idx_password_resets_ip (ip_address, created_at),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
