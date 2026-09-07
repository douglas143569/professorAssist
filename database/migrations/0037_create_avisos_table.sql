-- Mural de avisos da turma: professor publica, aluno so le.
CREATE TABLE IF NOT EXISTS avisos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    turma_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,           -- professor autor

    titulo VARCHAR(200) NOT NULL,
    corpo TEXT NOT NULL,
    fixado TINYINT(1) NOT NULL DEFAULT 0,    -- aparece no topo do mural e no painel do aluno

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_avisos_turma (turma_id),

    CONSTRAINT fk_avisos_turma FOREIGN KEY (turma_id)
        REFERENCES turmas(id) ON DELETE CASCADE,
    CONSTRAINT fk_avisos_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
