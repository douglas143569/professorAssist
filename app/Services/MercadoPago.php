<?php

namespace App\Services;

use GuzzleHttp\Client;
use RuntimeException;

class MercadoPago
{
    private Client $client;
    private string $accessToken;

    public function __construct()
    {
        $this->accessToken = trim((string) ($_ENV['MERCADO_PAGO_ACCESS_TOKEN'] ?? ''));
        if ($this->accessToken === '') {
            throw new RuntimeException('MERCADO_PAGO_ACCESS_TOKEN não está configurado.');
        }

        $this->client = new Client([
            'base_uri' => 'https://api.mercadopago.com/',
            'timeout' => 15,
            'connect_timeout' => 5,
            'http_errors' => false,
        ]);
    }

    public function createCheckoutPreference(array $plan, array $user, int $paymentId): array
    {
        $baseUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL) || !str_starts_with($baseUrl, 'https://')) {
            throw new RuntimeException('Configure APP_URL com o endereço HTTPS público da aplicação.');
        }

        $response = $this->client->post('checkout/preferences', [
            'headers' => $this->headers(),
            'json' => [
                'items' => [[
                    'id' => (string) $plan['id'],
                    'title' => 'Plano ' . $plan['name'],
                    'quantity' => 1,
                    'currency_id' => 'BRL',
                    'unit_price' => (float) $plan['price'],
                ]],
                'payer' => ['email' => $user['email']],
                'external_reference' => (string) $paymentId,
                'notification_url' => $baseUrl . '/webhook/mercadopago',
                'back_urls' => [
                    'success' => $baseUrl . '/assinatura/retorno?resultado=sucesso',
                    'pending' => $baseUrl . '/assinatura/retorno?resultado=pendente',
                    'failure' => $baseUrl . '/assinatura/retorno?resultado=falha',
                ],
                'auto_return' => 'approved',
                'statement_descriptor' => 'ASSIST PROFESSOR',
                'payment_methods' => ['installments' => 12],
            ],
        ]);

        $data = $this->decodeSuccessfulResponse($response->getStatusCode(), (string) $response->getBody());
        if (empty($data['id']) || empty($data['init_point'])) {
            throw new RuntimeException('Mercado Pago não retornou o endereço do checkout.');
        }

        return $data;
    }

    public function getPayment(string $paymentId): array
    {
        $response = $this->client->get('v1/payments/' . rawurlencode($paymentId), [
            'headers' => $this->headers(),
        ]);

        return $this->decodeSuccessfulResponse($response->getStatusCode(), (string) $response->getBody());
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    private function decodeSuccessfulResponse(int $statusCode, string $body): array
    {
        $data = json_decode($body, true);
        if ($statusCode < 200 || $statusCode >= 300 || !is_array($data)) {
            throw new RuntimeException('A API do Mercado Pago retornou uma resposta inválida (HTTP ' . $statusCode . ').');
        }

        return $data;
    }
}
