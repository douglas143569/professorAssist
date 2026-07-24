-- Banco de questoes reutilizavel. Cada questao pertence a uma disciplina e,
-- opcionalmente, a um modulo. Classificada por tipo, dificuldade e BNCC.
-- Questao gerada pela IA nasce como 'rascunho' ate o professor aprovar.
CREATE TABLE IF NOT EXISTS questoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    disciplina_id INT UNSIGNED NOT NULL,
    modulo_id INT UNSIGNED NULL,

    tipo ENUM('multipla_escolha','verdadeiro_falso','dissertativa') NOT NULL,
    enunciado TEXT NOT NULL,
    dificuldade ENUM('facil','media','dificil') NOT NULL DEFAULT 'media',
    habilidade_bncc VARCHAR(20) NULL,        -- ex: 'EF06MA07'
    tags VARCHAR(255) NULL,

    origem ENUM('ia','manual') NOT NULL DEFAULT 'manual',
    status ENUM('rascunho','aprovado') NOT NULL DEFAULT 'rascunho',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_questoes_disciplina (disciplina_id),
    INDEX idx_questoes_modulo (modulo_id),
    INDEX idx_questoes_status (status),
    INDEX idx_questoes_bncc (habilidade_bncc),

    CONSTRAINT fk_questoes_disciplina FOREIGN KEY (disciplina_id)
        REFERENCES disciplinas(id) ON DELETE CASCADE,
    CONSTRAINT fk_questoes_modulo FOREIGN KEY (modulo_id)
        REFERENCES modulos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
