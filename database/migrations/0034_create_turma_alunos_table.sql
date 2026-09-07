-- Matricula do aluno numa turma (roster). Base tambem da futura chamada/presenca.
-- Um aluno pode, no futuro, estar em mais de uma turma -- por isso tabela de ligacao.
CREATE TABLE IF NOT EXISTS turma_alunos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    turma_id INT UNSIGNED NOT NULL,
    aluno_id INT UNSIGNED NOT NULL,
    situacao ENUM('ativa','inativa') NOT NULL DEFAULT 'ativa',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_turma_aluno (turma_id, aluno_id),
    INDEX idx_turma_alunos_turma (turma_id),
    INDEX idx_turma_alunos_aluno (aluno_id),

    CONSTRAINT fk_turma_alunos_turma FOREIGN KEY (turma_id)
        REFERENCES turmas(id) ON DELETE CASCADE,
    CONSTRAINT fk_turma_alunos_aluno FOREIGN KEY (aluno_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
