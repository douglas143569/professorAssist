-- Autenticacao real (RF-01): papel, situacao da conta e ultimo acesso.
-- role: 'admin' administra o sistema; 'professor' usa apenas o proprio conteudo.
-- ativo: 0 bloqueia o login sem apagar os dados do professor.

ALTER TABLE users
    ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'professor' AFTER password_hash,
    ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
    ADD COLUMN last_login_at DATETIME NULL DEFAULT NULL AFTER ativo;

ALTER TABLE users ADD INDEX idx_users_role (role);
