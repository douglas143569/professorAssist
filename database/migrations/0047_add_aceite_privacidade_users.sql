-- LGPD: registro do aceite da Politica de Privacidade (quando e qual versao)
-- e marca de conta excluida/anonimizada (a linha fica so para vincular os
-- registros fiscais e de acesso que a lei manda reter).
ALTER TABLE users
    ADD COLUMN aceite_privacidade_em DATETIME NULL AFTER last_login_at,
    ADD COLUMN aceite_versao VARCHAR(20) NULL AFTER aceite_privacidade_em,
    ADD COLUMN excluido_em DATETIME NULL AFTER aceite_versao;
