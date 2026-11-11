<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\Logger;
use App\Services\PaymentWebhook;
use Throwable;

class MercadoPagoWebhookController extends Controller
{
    public function receber(): void
    {
        $body = json_decode((string) file_get_contents('php://input'), true);
        $providerPaymentId = $_GET['data_id'] ?? $_GET['id'] ?? ($body['data']['id'] ?? null);
        $type = $_GET['type'] ?? $body['type'] ?? $body['topic'] ?? '';

        if (!is_scalar($providerPaymentId) || !ctype_digit((string) $providerPaymentId) || $type !== 'payment') {
            http_response_code(400);
            echo 'Notificação inválida.';
            return;
        }

        $processor = new PaymentWebhook();
        if (!$processor->validSignature(
            (string) $providerPaymentId,
            $_SERVER['HTTP_X_SIGNATURE'] ?? null,
            $_SERVER['HTTP_X_REQUEST_ID'] ?? null
        )) {
            Logger::warning('Webhook Mercado Pago recusado: assinatura inválida', [
                'payment_id' => (string) $providerPaymentId,
            ]);
            http_response_code(401);
            echo 'Assinatura inválida.';
            return;
        }

        try {
            $processor->handle((string) $providerPaymentId);
            http_response_code(200);
            echo 'OK';
        } catch (Throwable $e) {
            Logger::exception($e);
            http_response_code(500);
            echo 'Falha ao processar notificação.';
        }
    }
}
