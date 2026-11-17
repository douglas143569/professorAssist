-- Cronograma MENSAL da creche: itens ganham objetivos, habilidades, avaliacao,
-- tipo de atividade e a semana/mes a que pertencem. O resumo do mes (objetivos,
-- competencias, adaptacoes, observacoes) fica em creche_cronograma_mes.
ALTER TABLE creche_cronograma
    ADD COLUMN objetivos TEXT NULL AFTER descricao,
    ADD COLUMN habilidades TEXT NULL AFTER objetivos,
    ADD COLUMN avaliacao TEXT NULL AFTER habilidades,
    ADD COLUMN tipo_atividade VARCHAR(80) NULL AFTER avaliacao,
    ADD COLUMN semana TINYINT UNSIGNED NULL AFTER tipo_atividade,
    ADD COLUMN mes_ref CHAR(7) NULL AFTER semana,
    ADD INDEX idx_cronograma_mes (user_id, mes_ref);

CREATE TABLE IF NOT EXISTS creche_cronograma_mes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    mes_ref CHAR(7) NOT NULL,                -- AAAA-MM
    faixa_etaria VARCHAR(40) NOT NULL,
    num_criancas SMALLINT UNSIGNED NULL,
    periodo VARCHAR(20) NOT NULL DEFAULT 'Integral',
    tema VARCHAR(200) NULL,
    resumo_objetivos TEXT NULL,
    competencias TEXT NULL,
    adaptacoes TEXT NULL,
    observacoes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cronograma_mes (user_id, mes_ref),
    CONSTRAINT fk_cronograma_mes_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
