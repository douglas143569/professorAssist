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

    /** Credenciais de teste do Mercado Pago começam com "TEST-". */
    public function isTestMode(): bool
    {
        return str_starts_with($this->accessToken, 'TEST-');
    }

    /** Valida o token consultando o próprio usuário/vendedor. */
    public function whoAmI(): array
    {
        $response = $this->client->get('users/me', ['headers' => $this->headers()]);

        return $this->decodeSuccessfulResponse($response->getStatusCode(), (string) $response->getBody());
    }

    public function createCheckoutPreference(array $plan, array $user, int $paymentId): array
    {
        $baseUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Configure APP_URL com o endereço da aplicação.');
        }

        // HTTPS é obrigatório em produção. Em teste/local aceitamos http://localhost,
        // mas então o Mercado Pago não consegue chamar o webhook nem redirecionar
        // automaticamente (use um túnel HTTPS, ex.: ngrok, para testar o fluxo completo).
        $https = str_starts_with($baseUrl, 'https://');
        $env = strtolower((string) ($_ENV['APP_ENV'] ?? 'production'));
        if (!$https && ($env === 'production' || !$this->isTestMode())) {
            throw new RuntimeException('Configure APP_URL com o endereço HTTPS público da aplicação.');
        }

        $payload = [
            'items' => [[
                'id' => (string) $plan['id'],
                'title' => 'Plano ' . $plan['name'],
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => (float) $plan['price'],
            ]],
            'payer' => ['email' => $user['email']],
            'external_reference' => (string) $paymentId,
            'back_urls' => [
                'success' => $baseUrl . '/assinatura/retorno?resultado=sucesso',
                'pending' => $baseUrl . '/assinatura/retorno?resultado=pendente',
                'failure' => $baseUrl . '/assinatura/retorno?resultado=falha',
            ],
            'statement_descriptor' => 'ASSIST PROFESSOR',
            'payment_methods' => ['installments' => 12],
        ];
        if ($https) {
            $payload['notification_url'] = $baseUrl . '/webhook/mercadopago';
            $payload['auto_return'] = 'approved';
        }

        $response = $this->client->post('checkout/preferences', [
            'headers' => $this->headers(),
            'json' => $payload,
        ]);

        $data = $this->decodeSuccessfulResponse($response->getStatusCode(), (string) $response->getBody());
        if (empty($data['id']) || empty($data['init_point'])) {
            throw new RuntimeException('Mercado Pago não retornou o endereço do checkout.');
        }

        // Com credenciais de teste, o init_point já abre o checkout em modo sandbox.
        return $data;
    }

    /** Chave pública (segura para o navegador) usada pelo MercadoPago.js. */
    public static function publicKey(): string
    {
        return trim((string) ($_ENV['MERCADO_PAGO_PUBLIC_KEY'] ?? ''));
    }

    /** URL base da aplicação, se for HTTPS (único caso em que o webhook alcança o servidor). */
    public static function notificationUrl(): ?string
    {
        $baseUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');

        return str_starts_with($baseUrl, 'https://') ? $baseUrl . '/webhook/mercadopago' : null;
    }

    /**
     * Cria um pagamento direto (Checkout Transparente): PIX ou cartão tokenizado.
     * Pagamento recusado NÃO lança exceção: volta com status "rejected".
     */
    public function createPayment(array $payload, string $idempotencyKey, ?string $deviceId = null): array
    {
        $headers = $this->headers() + ['X-Idempotency-Key' => $idempotencyKey];
        if ($deviceId !== null && $deviceId !== '') {
            $headers['X-meli-session-id'] = $deviceId;
        }

        $response = $this->client->post('v1/payments', ['headers' => $headers, 'json' => $payload]);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300 || !is_array($data) || empty($data['id'])) {
            $detalhe = is_array($data) ? ($data['message'] ?? '') . ' ' . json_encode($data['cause'] ?? []) : $body;
            throw new RuntimeException('Mercado Pago recusou a criação do pagamento (HTTP '
                . $response->getStatusCode() . '): ' . trim($detalhe));
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
