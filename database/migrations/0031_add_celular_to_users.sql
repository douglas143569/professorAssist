-- Celular de contato do professor, informado no cadastro.
-- Guardado so com digitos (ex: 11987654321); a formatacao e feita na tela.
-- Dado de adulto (professor); nenhum dado de aluno entra aqui.

ALTER TABLE users
    ADD COLUMN celular VARCHAR(11) NULL AFTER email;
