-- Modulos: unidades de ensino dentro de uma disciplina.
-- Guardam objetivos e as habilidades BNCC associadas.
CREATE TABLE IF NOT EXISTS modulos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    disciplina_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(200) NOT NULL,            -- ex: 'Fracoes'
    ordem INT UNSIGNED NOT NULL DEFAULT 0,   -- ordem de exibicao na disciplina
    objetivos TEXT NULL,                     -- objetivos de aprendizagem
    codigos_bncc VARCHAR(255) NULL,          -- ex: 'EF06MA07,EF06MA08'

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_modulos_disciplina (disciplina_id, ordem),

    CONSTRAINT fk_modulos_disciplina FOREIGN KEY (disciplina_id)
        REFERENCES disciplinas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
