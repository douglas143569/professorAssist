-- Assistente de apresentacoes: roteiro de slides + resumo para a aula
-- expositiva de um tema. Nasce da IA como rascunho e vale apos revisao.
CREATE TABLE IF NOT EXISTS apresentacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    modulo_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    foco VARCHAR(160) NULL,                  -- recorte pedido pelo professor

    slides_json LONGTEXT NULL,               -- [{titulo, topicos:[...], nota}]
    resumo LONGTEXT NULL,                    -- texto corrido (linhas "- " para listas)

    origem ENUM('ia','manual') NOT NULL DEFAULT 'manual',
    status ENUM('rascunho','aprovado') NOT NULL DEFAULT 'rascunho',
    material_id INT UNSIGNED NULL,           -- material do aluno gerado a partir do resumo

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_apresentacoes_modulo (modulo_id),
    INDEX idx_apresentacoes_status (status),

    CONSTRAINT fk_apresentacoes_modulo FOREIGN KEY (modulo_id)
        REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
