-- Planos de aula: documento pedagogico estruturado de um modulo
-- (objetivos, metodologia, recursos, avaliacao). Diferente de 'conteudos',
-- que guarda o material em si. Pode nascer da IA e vale apos revisao.
CREATE TABLE IF NOT EXISTS planos_aula (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    modulo_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    duracao VARCHAR(80) NULL,                -- ex: '2 aulas de 50 min'
    corpo LONGTEXT NULL,                     -- plano completo (markdown)

    origem ENUM('ia','manual') NOT NULL DEFAULT 'manual',
    status ENUM('rascunho','aprovado') NOT NULL DEFAULT 'rascunho',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_planos_modulo (modulo_id),
    INDEX idx_planos_status (status),

    CONSTRAINT fk_planos_modulo FOREIGN KEY (modulo_id)
        REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
