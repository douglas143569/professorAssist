-- Pacotes de atividades da creche: um conjunto tematico de atividades
-- variadas (memoria, pintura, musica, movimento...). Gerado pela IA.
CREATE TABLE IF NOT EXISTS creche_pacotes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NOT NULL,
    faixa_etaria VARCHAR(40) NOT NULL,
    tema VARCHAR(200) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_pacotes_user (user_id),

    CONSTRAINT fk_pacotes_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
