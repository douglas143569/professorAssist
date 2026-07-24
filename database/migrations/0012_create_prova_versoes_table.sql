-- Versoes embaralhadas de uma prova (A, B, C...). O seed garante que a mesma
-- versao gera sempre o mesmo embaralhamento de questoes e alternativas.
CREATE TABLE IF NOT EXISTS prova_versoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    prova_id INT UNSIGNED NOT NULL,
    rotulo VARCHAR(5) NOT NULL,              -- 'A', 'B', 'C'
    seed_embaralhamento INT UNSIGNED NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_prova_versao (prova_id, rotulo),
    INDEX idx_prova_versoes_prova (prova_id),

    CONSTRAINT fk_prova_versoes_prova FOREIGN KEY (prova_id)
        REFERENCES provas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
