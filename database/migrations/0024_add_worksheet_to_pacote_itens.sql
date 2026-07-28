-- Transforma os itens do pacote em FOLHAS imprimiveis: uma instrucao e uma
-- lista de itens (figura/emoji + resposta), guardada em itens_json.
ALTER TABLE creche_pacote_itens
    ADD COLUMN instrucao TEXT NULL AFTER titulo,
    ADD COLUMN itens_json JSON NULL AFTER instrucao;
