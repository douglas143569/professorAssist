-- Cache de prompts: o prompt_hash ja existia, mas a resposta nao era guardada,
-- entao nao havia o que reaproveitar. Guardando a resposta, um pedido identico
-- (mesmo modelo + mesmo prompt) e servido do banco, sem nova chamada a API.
--
-- status 'cache' registra o reaproveitamento em ai_geracoes com custo zero,
-- mantendo a auditoria de uso completa e tornando a economia visivel.

ALTER TABLE ai_geracoes
    ADD COLUMN resposta LONGTEXT NULL AFTER prompt_hash,
    MODIFY COLUMN status ENUM('ok','erro','cache') NOT NULL DEFAULT 'ok';

-- Busca do cache: hash + dono + status, do mais recente para o mais antigo.
ALTER TABLE ai_geracoes ADD INDEX idx_ai_cache (prompt_hash, user_id, status);
