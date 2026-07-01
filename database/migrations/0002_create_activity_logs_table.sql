-- Auditoria de negocio: registra ACOES relevantes feitas no sistema.
-- Ex: usuario publicou um post, admin criou um registro, perfil atualizado.
-- Responde "quem fez o que, quando e sobre qual registro".
CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NULL,               -- quem executou (NULL = sistema/anonimo)
    action VARCHAR(100) NOT NULL,            -- ex: 'post.created', 'order.completed'

    entity_type VARCHAR(60) NULL,            -- ex: 'post', 'order', 'user'
    entity_id BIGINT UNSIGNED NULL,          -- id do registro afetado

    description VARCHAR(255) NULL,           -- texto legivel para humanos
    properties JSON NULL,                    -- dados extras (antes/depois, payload)

    ip_address VARCHAR(45) NULL,             -- suporta IPv6
    user_agent VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_activity_user (user_id),
    INDEX idx_activity_action (action),
    INDEX idx_activity_entity (entity_type, entity_id),
    INDEX idx_activity_created (created_at),

    CONSTRAINT fk_activity_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
