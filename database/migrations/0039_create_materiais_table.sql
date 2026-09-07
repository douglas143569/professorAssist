-- Materiais de apoio da turma, visiveis na Area do Aluno.
-- Fatia minima: por ora so 'resumo' (texto). Depois: pdf, link, video.
CREATE TABLE IF NOT EXISTS materiais (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    turma_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,           -- professor autor

    titulo VARCHAR(200) NOT NULL,
    corpo LONGTEXT NOT NULL,
    tipo VARCHAR(20) NOT NULL DEFAULT 'resumo',
    origem VARCHAR(40) NULL,                 -- ex: 'apresentacao:123' (1 material por origem)

    publicado TINYINT(1) NOT NULL DEFAULT 1,
    publicado_em DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_materiais_origem (origem),
    INDEX idx_materiais_turma (turma_id),

    CONSTRAINT fk_materiais_turma FOREIGN KEY (turma_id)
        REFERENCES turmas(id) ON DELETE CASCADE,
    CONSTRAINT fk_materiais_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
