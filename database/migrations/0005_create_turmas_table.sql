-- Turmas: rotulo organizacional (ex: '6 A', 'Turma manha').
-- No MVP nao ha alunos vinculados; serve so para organizar as disciplinas.
CREATE TABLE IF NOT EXISTS turmas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    disciplina_id INT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,              -- ex: '6 A', 'Turma da tarde'

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_turmas_disciplina (disciplina_id),

    CONSTRAINT fk_turmas_disciplina FOREIGN KEY (disciplina_id)
        REFERENCES disciplinas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
