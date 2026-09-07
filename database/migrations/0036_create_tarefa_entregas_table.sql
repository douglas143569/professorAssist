-- Entrega de um aluno para uma tarefa.
-- O status "pendente" NAO tem linha aqui: e a ausencia de registro para
-- (tarefa_id, aluno_id). Ao enviar, cria 'entregue'; ao corrigir, vira 'corrigida'.
CREATE TABLE IF NOT EXISTS tarefa_entregas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    tarefa_id INT UNSIGNED NOT NULL,
    aluno_id INT UNSIGNED NOT NULL,

    resposta_texto TEXT NULL,
    arquivo_path VARCHAR(255) NULL,          -- caminho relativo em storage/uploads
    arquivo_nome VARCHAR(255) NULL,          -- nome original, para download

    status ENUM('entregue','corrigida') NOT NULL DEFAULT 'entregue',
    nota DECIMAL(5,2) NULL,
    feedback TEXT NULL,

    entregue_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    corrigida_em DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_tarefa_aluno (tarefa_id, aluno_id),
    INDEX idx_entregas_aluno (aluno_id),

    CONSTRAINT fk_entregas_tarefa FOREIGN KEY (tarefa_id)
        REFERENCES tarefas(id) ON DELETE CASCADE,
    CONSTRAINT fk_entregas_aluno FOREIGN KEY (aluno_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
