-- Itens de um pacote de atividades: cada um e uma atividade de um tipo
-- (ex: 'Brincadeira de memoria', 'Desenho para pintar', 'Musica').
CREATE TABLE IF NOT EXISTS creche_pacote_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    pacote_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(60) NULL,                   -- categoria da atividade
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NULL,
    materiais TEXT NULL,
    duracao VARCHAR(60) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_pacote_itens_pacote (pacote_id),

    CONSTRAINT fk_pacote_itens_pacote FOREIGN KEY (pacote_id)
        REFERENCES creche_pacotes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
