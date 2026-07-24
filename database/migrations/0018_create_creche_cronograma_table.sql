-- Cronograma semanal de atividades da creche: cada item e uma atividade
-- ludica planejada para um dia. Gerado pela IA (semana inteira) e editavel.
-- Nao guarda dado de crianca.
CREATE TABLE IF NOT EXISTS creche_cronograma (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NOT NULL,
    faixa_etaria VARCHAR(40) NOT NULL,
    campo_experiencia VARCHAR(80) NULL,

    data DATE NOT NULL,                      -- dia planejado
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NULL,
    materiais TEXT NULL,
    duracao VARCHAR(60) NULL,

    origem ENUM('ia','manual') NOT NULL DEFAULT 'ia',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_cronograma_user_data (user_id, data),

    CONSTRAINT fk_cronograma_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
