-- Campo de experiencia da BNCC (Educacao Infantil) usado para alinhar as
-- atividades geradas pela IA no pacote.
ALTER TABLE creche_pacotes
    ADD COLUMN campo_experiencia VARCHAR(80) NULL AFTER faixa_etaria;

-- Campo de experiencia da BNCC que cada atividade do pacote trabalha
-- especificamente (a IA pode variar entre campos dentro de um mesmo pacote).
ALTER TABLE creche_pacote_itens
    ADD COLUMN campo_bncc VARCHAR(80) NULL AFTER tipo;
