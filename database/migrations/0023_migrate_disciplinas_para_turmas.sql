-- Move as materias existentes (sem turma) para uma turma padrao por professor,
-- herdando a etapa/ano da primeira materia daquele professor.
INSERT INTO turmas (user_id, nome, etapa, ano_serie)
SELECT d.user_id, 'Minha turma',
       (SELECT etapa FROM disciplinas WHERE user_id = d.user_id ORDER BY id LIMIT 1),
       (SELECT ano_serie FROM disciplinas WHERE user_id = d.user_id ORDER BY id LIMIT 1)
FROM disciplinas d
WHERE d.turma_id IS NULL
GROUP BY d.user_id;

UPDATE disciplinas d
JOIN turmas t ON t.user_id = d.user_id AND t.nome = 'Minha turma'
SET d.turma_id = t.id
WHERE d.turma_id IS NULL;
