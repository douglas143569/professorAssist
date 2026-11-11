-- Plano interno de teste gratis (3 dias). active = 0 para NAO aparecer na
-- tela de escolha de planos (Plan::active() so lista active = 1); ele so e
-- usado pelo cadastro, via Subscription::startTrial().
INSERT INTO plans (name, price, duration_days, active) VALUES
    ('Teste grátis', 0.00, 3, 0)
ON DUPLICATE KEY UPDATE price = VALUES(price), duration_days = VALUES(duration_days), active = 0;
