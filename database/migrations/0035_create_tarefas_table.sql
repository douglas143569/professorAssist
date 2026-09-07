-- Tarefas que o professor libera para uma turma (RF area do aluno).
-- Nasce como rascunho (publicada=0); o aluno so ve depois de publicada.
CREATE TABLE IF NOT EXISTS tarefas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    turma_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,            -- professor autor
    disciplina_id INT UNSIGNED NULL,          -- opcional: materia da tarefa

    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NULL,                      -- enunciado / instrucoes
    tipo_entrega ENUM('texto','arquivo','ambos') NOT NULL DEFAULT 'ambos',
    entrega_ate DATETIME NULL,                -- prazo (opcional)

    publicada TINYINT(1) NOT NULL DEFAULT 0,
    publicada_em DATETIME NULL,

    origem_tipo VARCHAR(20) NULL,             -- futuro: 'prova','atividade','conteudo'
    origem_id INT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_tarefas_turma (turma_id),
    INDEX idx_tarefas_pub (turma_id, publicada),

    CONSTRAINT fk_tarefas_turma FOREIGN KEY (turma_id)
        REFERENCES turmas(id) ON DELETE CASCADE,
    CONSTRAINT fk_tarefas_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
