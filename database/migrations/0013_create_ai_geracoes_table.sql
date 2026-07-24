-- Registro de cada chamada ao assistente de IA. Base para controle de custo
-- (tokens e custo estimado), cache (prompt_hash) e auditoria de uso por professor.
CREATE TABLE IF NOT EXISTS ai_geracoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NULL,               -- professor que disparou (NULL = sistema/cli)
    tipo VARCHAR(50) NOT NULL,               -- ex: 'conteudo', 'questao', 'prova'
    modelo VARCHAR(60) NULL,                  -- ex: 'claude-haiku-4-5-20251001'

    prompt_hash CHAR(64) NULL,               -- sha256 do prompt (lookup de cache)
    tokens_in INT UNSIGNED NULL,
    tokens_out INT UNSIGNED NULL,
    custo_estimado DECIMAL(10,6) NULL,       -- em USD

    status ENUM('ok','erro') NOT NULL DEFAULT 'ok',
    erro VARCHAR(255) NULL,                   -- mensagem quando status='erro'

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_ai_user (user_id),
    INDEX idx_ai_tipo (tipo),
    INDEX idx_ai_prompt_hash (prompt_hash),
    INDEX idx_ai_created (created_at),

    CONSTRAINT fk_ai_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
