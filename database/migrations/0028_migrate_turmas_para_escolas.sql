-- Move as turmas existentes (sem escola) para uma escola padrao por professor
-- e, com todo mundo ligado, torna escola_id obrigatoria.
INSERT INTO escolas (user_id, nome)
SELECT t.user_id, 'Minha escola'
FROM turmas t
WHERE t.escola_id IS NULL
GROUP BY t.user_id;

UPDATE turmas t
JOIN escolas e ON e.user_id = t.user_id AND e.nome = 'Minha escola'
SET t.escola_id = e.id
WHERE t.escola_id IS NULL;

ALTER TABLE turmas MODIFY escola_id INT UNSIGNED NOT NULL;
