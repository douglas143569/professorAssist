-- Sugestoes de atividades de um modulo (banco reutilizavel).
-- Podem nascer da IA e valem apos revisao do professor.
CREATE TABLE IF NOT EXISTS atividades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    modulo_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NULL,                     -- passo a passo da atividade
    formato VARCHAR(30) NULL,                -- individual, grupo, discussao, pratica, projeto, jogo
    duracao VARCHAR(60) NULL,                -- ex: '20 min'

    origem ENUM('ia','manual') NOT NULL DEFAULT 'manual',
    status ENUM('rascunho','aprovado') NOT NULL DEFAULT 'rascunho',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_atividades_modulo (modulo_id),
    INDEX idx_atividades_status (status),

    CONSTRAINT fk_atividades_modulo FOREIGN KEY (modulo_id)
        REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
