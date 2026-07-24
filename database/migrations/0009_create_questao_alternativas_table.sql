-- Alternativas de uma questao (para multipla escolha e verdadeiro/falso).
-- 'correta' marca o gabarito. Dissertativas normalmente nao tem alternativas.
CREATE TABLE IF NOT EXISTS questao_alternativas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    questao_id INT UNSIGNED NOT NULL,
    texto TEXT NOT NULL,
    correta TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = alternativa correta
    ordem INT UNSIGNED NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_alternativas_questao (questao_id),

    CONSTRAINT fk_alternativas_questao FOREIGN KEY (questao_id)
        REFERENCES questoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
