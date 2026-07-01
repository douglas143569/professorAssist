-- Seguranca / acessos: registra eventos de autenticacao.
-- Ex: login com sucesso, login falho, logout.
-- Usado para auditoria de seguranca e rate limiting (bloquear brute force).
CREATE TABLE IF NOT EXISTS access_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NULL,               -- NULL quando o email nem existe
    email VARCHAR(150) NULL,                 -- email tentado (mesmo que invalido)

    event ENUM('login_success','login_failed','logout','password_reset')
        NOT NULL,

    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_access_user (user_id),
    INDEX idx_access_email (email),
    INDEX idx_access_event (event),
    INDEX idx_access_ip_created (ip_address, created_at),   -- rate limiting por IP

    CONSTRAINT fk_access_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
