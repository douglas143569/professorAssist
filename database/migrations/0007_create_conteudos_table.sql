-- Conteudos: o material a ser passado em cada modulo.
-- Pode nascer da IA (origem='ia') ou ser escrito a mao (origem='manual').
-- So vale para uso depois de revisado (status='aprovado').
CREATE TABLE IF NOT EXISTS conteudos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    modulo_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    corpo LONGTEXT NULL,                     -- texto do conteudo (HTML/markdown)

    origem ENUM('ia','manual') NOT NULL DEFAULT 'manual',
    status ENUM('rascunho','aprovado') NOT NULL DEFAULT 'rascunho',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_conteudos_modulo (modulo_id),
    INDEX idx_conteudos_status (status),

    CONSTRAINT fk_conteudos_modulo FOREIGN KEY (modulo_id)
        REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
