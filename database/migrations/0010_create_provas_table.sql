-- Provas montadas pelo professor a partir do banco de questoes.
-- 'config' guarda opcoes de geracao (num de versoes, embaralhar, etc) em JSON.
CREATE TABLE IF NOT EXISTS provas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    disciplina_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,           -- professor dono
    titulo VARCHAR(200) NOT NULL,
    instrucoes TEXT NULL,
    config JSON NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_provas_disciplina (disciplina_id),
    INDEX idx_provas_user (user_id),

    CONSTRAINT fk_provas_disciplina FOREIGN KEY (disciplina_id)
        REFERENCES disciplinas(id) ON DELETE CASCADE,
    CONSTRAINT fk_provas_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
