-- Liga cada turma a uma escola. Fica NULL nesta migration; a 0028 preenche
-- os registros antigos e so entao torna a coluna obrigatoria.
ALTER TABLE turmas
    ADD COLUMN escola_id INT UNSIGNED NULL AFTER user_id,
    ADD INDEX idx_turmas_escola (escola_id),
    ADD CONSTRAINT fk_turmas_escola FOREIGN KEY (escola_id)
        REFERENCES escolas(id) ON DELETE CASCADE;
