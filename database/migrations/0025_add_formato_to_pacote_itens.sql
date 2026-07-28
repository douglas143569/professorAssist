-- Formato da folha: define como a atividade e respondida/renderizada.
-- 'escrever' (figura -> quadro) ou 'circular' (linha de opcoes -> circule a certa).
ALTER TABLE creche_pacote_itens
    ADD COLUMN formato VARCHAR(20) NULL AFTER tipo;
