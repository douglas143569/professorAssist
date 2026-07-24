-- Aba Creche (Educacao Infantil): atividades ludicas por faixa etaria e
-- campo de experiencia da BNCC. Nao guarda dado de crianca (so atividades).
CREATE TABLE IF NOT EXISTS creche_atividades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NOT NULL,           -- professor dono
    faixa_etaria VARCHAR(40) NOT NULL,       -- ex: 'Maternal (2-3 anos)'
    campo_experiencia VARCHAR(80) NULL,      -- campo de experiencia BNCC EI

    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NULL,                     -- passo a passo da brincadeira
    materiais TEXT NULL,                     -- materiais necessarios
    duracao VARCHAR(60) NULL,                -- ex: '15 min'

    origem ENUM('ia','manual') NOT NULL DEFAULT 'manual',
    status ENUM('rascunho','aprovado') NOT NULL DEFAULT 'rascunho',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_creche_user (user_id),
    INDEX idx_creche_faixa (faixa_etaria),

    CONSTRAINT fk_creche_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
