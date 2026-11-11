-- Plano Trimestral: valor total de R$ 200,00 por 90 dias (antes 65,90).
-- Pagamentos ja criados guardam o proprio valor (payments.amount) e nao mudam.
UPDATE plans SET price = 200.00 WHERE name = 'Trimestral';
