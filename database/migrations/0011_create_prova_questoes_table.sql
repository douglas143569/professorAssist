-- Vinculo prova <-> questoes, com ordem e pontuacao de cada questao na prova.
CREATE TABLE IF NOT EXISTS prova_questoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    prova_id INT UNSIGNED NOT NULL,
    questao_id INT UNSIGNED NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    pontuacao DECIMAL(5,2) NOT NULL DEFAULT 1.00,

    UNIQUE KEY uq_prova_questao (prova_id, questao_id),
    INDEX idx_prova_questoes_prova (prova_id),

    CONSTRAINT fk_prova_questoes_prova FOREIGN KEY (prova_id)
        REFERENCES provas(id) ON DELETE CASCADE,
    CONSTRAINT fk_prova_questoes_questao FOREIGN KEY (questao_id)
        REFERENCES questoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
