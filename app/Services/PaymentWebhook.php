<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;

class PaymentWebhook
{
    public function validSignature(string $paymentId, ?string $signature, ?string $requestId): bool
    {
        $secret = trim((string) ($_ENV['MERCADO_PAGO_WEBHOOK_SECRET'] ?? ''));
        if ($secret === '' || $signature === null || $requestId === null) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signature) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }
        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }

        $manifest = 'id:' . strtolower($paymentId) . ';request-id:' . $requestId . ';ts:' . $parts['ts'] . ';';
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $parts['v1']);
    }

    public function handle(string $providerPaymentId, ?int $onlyUserId = null): void
    {
        $this->apply((new MercadoPago())->getPayment($providerPaymentId), $onlyUserId);
    }

    /** Aplica ao pedido local o estado de um pagamento já consultado na API do Mercado Pago. */
    public function apply(array $providerPayment, ?int $onlyUserId = null): void
    {
        $reference = filter_var($providerPayment['external_reference'] ?? null, FILTER_VALIDATE_INT);
        $localPayment = $reference ? (new Payment())->findByExternalReference((int) $reference) : null;

        if ($localPayment === null) {
            throw new \RuntimeException('A notificação não corresponde a um pagamento local.');
        }

        if ($onlyUserId !== null && (int) $localPayment['user_id'] !== $onlyUserId) {
            throw new \RuntimeException('O pagamento não pertence ao usuário autenticado.');
        }

        $expectedAmount = (int) round((float) $localPayment['amount'] * 100);
        $receivedAmount = (int) round((float) ($providerPayment['transaction_amount'] ?? 0) * 100);
        if ($expectedAmount !== $receivedAmount || ($providerPayment['currency_id'] ?? '') !== 'BRL') {
            throw new \RuntimeException('Valor ou moeda do pagamento não corresponde ao pedido local.');
        }

        $status = (string) ($providerPayment['status'] ?? '');
        $method = isset($providerPayment['payment_method_id']) ? (string) $providerPayment['payment_method_id'] : null;
        if ($status === 'approved') {
            (new Payment())->recordProviderStatus(
                (int) $reference,
                (string) $providerPayment['id'],
                'approved',
                $method
            );
            if (!(new Subscription())->activatePaid((int) $reference)) {
                throw new \RuntimeException('Não foi possível ativar a assinatura do pagamento confirmado.');
            }
            Logger::activity('subscription.activated', [
                'user_id' => (int) $localPayment['user_id'],
                'entity_type' => 'subscription',
                'entity_id' => (int) $localPayment['subscription_id'],
                'description' => 'Pagamento confirmado pelo Mercado Pago.',
            ]);
            return;
        }

        (new Payment())->setNonApprovedStatus(
            (int) $reference,
            $status,
            (string) $providerPayment['id'],
            $method
        );
        Logger::activity('payment.status_updated', [
            'user_id' => (int) $localPayment['user_id'],
            'entity_type' => 'payment',
            'entity_id' => (int) $reference,
            'description' => 'Status de pagamento atualizado pelo Mercado Pago: ' . $status,
        ]);
    }
}
