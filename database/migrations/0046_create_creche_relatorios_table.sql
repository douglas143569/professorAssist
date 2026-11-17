-- Banco de relatorios individuais de alunos da creche.
-- ATENCAO: guarda nome e observacoes de menores (dado pessoal). Cada registro
-- pertence a um professor (user_id) e so ele o enxerga; pode ser excluido a
-- qualquer momento. Nao vai para logs nem para o historico de IA.
CREATE TABLE IF NOT EXISTS creche_relatorios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    aluno_nome VARCHAR(120) NOT NULL,
    idade VARCHAR(60) NOT NULL,
    turma VARCHAR(80) NOT NULL,
    periodo VARCHAR(80) NOT NULL,
    professor VARCHAR(120) NOT NULL,
    texto LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_relatorios_user (user_id, created_at),
    CONSTRAINT fk_relatorios_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
