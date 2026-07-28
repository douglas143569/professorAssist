-- Vincula cada Materia (disciplinas) a uma Turma. Nullable para nao quebrar
-- dados existentes; a migration 0023 preenche.
ALTER TABLE disciplinas
    ADD COLUMN turma_id INT UNSIGNED NULL AFTER user_id,
    ADD INDEX idx_disciplinas_turma (turma_id),
    ADD CONSTRAINT fk_disciplinas_turma FOREIGN KEY (turma_id)
        REFERENCES turmas(id) ON DELETE CASCADE;
