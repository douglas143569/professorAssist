<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserPromotion;
use App\Services\Logger;
use App\Services\MercadoPago;
use App\Services\PaymentWebhook;
use Throwable;

/**
 * Pagamento dentro do próprio sistema (Checkout Transparente do Mercado Pago):
 * PIX (QR Code + copia e cola) e cartão de crédito (token gerado no navegador;
 * o número do cartão nunca passa pelo nosso servidor).
 *
 * A ativação da assinatura usa o mesmo caminho do webhook (PaymentWebhook::apply),
 * que confere valor/moeda e é idempotente.
 */
class PagamentoController extends AppController
{
    public function pagina(string $plano): void
    {
        $this->exigirProfessor();
        $user = $this->professor();

        $planRow = (new Plan())->findActive((int) $plano);
        if ($planRow === null) {
            $this->flash('Plano inválido ou indisponível.');
            $this->redirect('/assinatura');
        }

        // Promoção individual só vale se estiver disponível para esta conta e este plano.
        $promotion = null;
        $promoId = filter_var($_GET['promocao'] ?? null, FILTER_VALIDATE_INT);
        if ($promoId) {
            foreach ((new UserPromotion())->forUser((int) $user['id']) as $candidate) {
                if ((int) $candidate['id'] === $promoId
                    && (int) $candidate['plan_id'] === (int) $planRow['id']
                    && $candidate['status'] === 'available') {
                    $promotion = $candidate;
                }
            }
        }

        $mp = null;
        try {
            $mp = new MercadoPago();
        } catch (Throwable) {
            // token ausente: a página avisa em vez de quebrar
        }

        $this->view('assinatura.pagar', [
            'title' => 'Pagamento',
            'plan' => $planRow,
            'price' => $promotion ? (float) $promotion['promotional_price'] : (float) $planRow['price'],
            'promotion' => $promotion,
            'configured' => $mp !== null,
            'testMode' => $mp?->isTestMode() ?? false,
            'publicKey' => MercadoPago::publicKey(),
            'userEmail' => (string) $user['email'],
        ]);
    }

    /** Gera um pagamento PIX e devolve o QR Code. */
    public function pix(): void
    {
        $ctx = $this->iniciar();
        if ($ctx === null) {
            return;
        }
        ['plan' => $plan, 'user' => $user, 'paymentId' => $paymentId, 'price' => $price, 'cpf' => $cpf] = $ctx;

        try {
            $payload = $this->payloadBase($plan, $user, $paymentId, $price, $cpf) + [
                'payment_method_id' => 'pix',
                'date_of_expiration' => date('Y-m-d\TH:i:s.000P', time() + 30 * 60),
            ];
            $resp = (new MercadoPago())->createPayment($payload, 'assist-pix-' . $paymentId);

            $dados = $resp['point_of_interaction']['transaction_data'] ?? [];
            if (empty($dados['qr_code'])) {
                throw new \RuntimeException('Mercado Pago não retornou o código PIX.');
            }

            (new Payment())->setNonApprovedStatus($paymentId, 'pending', (string) $resp['id'], 'pix');
            Logger::activity('payment.pix_created', [
                'user_id' => (int) $user['id'],
                'entity_type' => 'payment',
                'entity_id' => $paymentId,
                'description' => 'PIX gerado para o plano ' . $plan['name'],
            ]);

            $this->json([
                'ok' => true,
                'pagamento' => $paymentId,
                'qr_code' => $dados['qr_code'],
                'qr_code_base64' => $dados['qr_code_base64'] ?? null,
                'expira_em' => 30 * 60,
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);
            (new Payment())->failCheckout($paymentId);
            $this->json(['ok' => false, 'mensagem' => 'Não foi possível gerar o PIX agora. Tente novamente.'], 502);
        }
    }

    /** Cobra no cartão de crédito usando o token gerado pelo MercadoPago.js. */
    public function cartao(): void
    {
        $token = trim((string) ($_POST['token'] ?? ''));
        $metodo = (string) ($_POST['payment_method_id'] ?? '');
        $parcelas = filter_var($_POST['installments'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        $issuer = trim((string) ($_POST['issuer_id'] ?? ''));

        if ($token === '' || !preg_match('/^[a-z0-9_]{2,40}$/', $metodo) || !$parcelas || ($issuer !== '' && !ctype_digit($issuer))) {
            $this->json(['ok' => false, 'mensagem' => 'Dados do cartão incompletos. Confira e tente de novo.'], 422);
            return;
        }

        $ctx = $this->iniciar();
        if ($ctx === null) {
            return;
        }
        ['plan' => $plan, 'user' => $user, 'paymentId' => $paymentId, 'price' => $price, 'cpf' => $cpf] = $ctx;

        try {
            $payload = $this->payloadBase($plan, $user, $paymentId, $price, $cpf) + [
                'token' => $token,
                'installments' => $parcelas,
                'payment_method_id' => $metodo,
            ];
            if ($issuer !== '') {
                $payload['issuer_id'] = (int) $issuer;
            }

            $device = substr(preg_replace('/[^A-Za-z0-9_\-]/', '', (string) ($_POST['device_id'] ?? '')), 0, 100);
            $resp = (new MercadoPago())->createPayment($payload, 'assist-card-' . $paymentId, $device);

            (new PaymentWebhook())->apply($resp, (int) $user['id']);

            $status = (string) ($resp['status'] ?? '');
            $this->json([
                'ok' => $status === 'approved' || $status === 'in_process' || $status === 'pending',
                'status' => $status,
                'pagamento' => $paymentId,
                'mensagem' => $this->mensagemCartao($status, (string) ($resp['status_detail'] ?? '')),
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);
            (new Payment())->failCheckout($paymentId);
            $this->json(['ok' => false, 'status' => 'error', 'mensagem' => 'Não foi possível processar o cartão. Confira os dados ou tente outro cartão.'], 502);
        }
    }

    /** Consulta (polling) do status de um pagamento do usuário logado. */
    public function status(): void
    {
        $this->exigirProfessor();
        $userId = (int) $this->professor()['id'];
        $id = filter_var($_GET['pagamento'] ?? null, FILTER_VALIDATE_INT);
        $payment = $id ? (new Payment())->findForUser((int) $id, $userId) : null;

        if ($payment === null) {
            $this->json(['status' => 'unknown'], 404);
            return;
        }

        // Sem webhook alcançável (ex.: teste local), confirma consultando o Mercado Pago.
        if ($payment['status'] !== 'approved' && !empty($payment['transaction_id'])) {
            try {
                (new PaymentWebhook())->handle((string) $payment['transaction_id'], $userId);
                $payment = (new Payment())->findForUser((int) $id, $userId) ?? $payment;
            } catch (Throwable $e) {
                Logger::exception($e);
            }
        }

        $this->json(['status' => $payment['status']]);
    }

    /**
     * Valida a requisição e cria o pedido local (assinatura + pagamento pendentes).
     * Responde o erro em JSON e retorna null quando algo não confere.
     */
    private function iniciar(): ?array
    {
        $this->exigirProfessor();
        $user = $this->professor();

        $cpf = preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? ''));
        if (!$this->cpfValido($cpf)) {
            $this->json(['ok' => false, 'mensagem' => 'Informe um CPF válido.'], 422);
            return null;
        }

        $plan = (new Plan())->findActive((int) ($_POST['plan_id'] ?? 0));
        if ($plan === null) {
            $this->json(['ok' => false, 'mensagem' => 'Plano inválido ou indisponível.'], 422);
            return null;
        }

        try {
            $promoId = filter_var($_POST['promotion_id'] ?? null, FILTER_VALIDATE_INT);
            if ($promoId) {
                $reserva = (new UserPromotion())->reserveCheckout((int) $user['id'], (int) $plan['id'], (int) $promoId);
                $paymentId = (int) $reserva['payment_id'];
                $price = (float) $reserva['price'];
            } else {
                $subscriptionId = (new Subscription())->createPending((int) $user['id'], (int) $plan['id']);
                $price = (float) $plan['price'];
                $paymentId = (new Payment())->createPending((int) $user['id'], $subscriptionId, $price);
            }
        } catch (Throwable $e) {
            Logger::exception($e);
            $this->json(['ok' => false, 'mensagem' => 'Esta oferta não está mais disponível. Atualize a página.'], 409);
            return null;
        }

        return ['plan' => $plan, 'user' => $user, 'paymentId' => $paymentId, 'price' => $price, 'cpf' => $cpf];
    }

    private function payloadBase(array $plan, array $user, int $paymentId, float $price, string $cpf): array
    {
        $partes = preg_split('/\s+/', trim((string) $user['name'])) ?: [];
        $payload = [
            'transaction_amount' => round($price, 2),
            'description' => 'Plano ' . $plan['name'],
            'external_reference' => (string) $paymentId,
            'statement_descriptor' => 'ASSIST PROFESSOR',
            'payer' => [
                'email' => $user['email'],
                'first_name' => $partes[0] ?? '',
                'last_name' => count($partes) > 1 ? implode(' ', array_slice($partes, 1)) : '',
                'identification' => ['type' => 'CPF', 'number' => $cpf],
            ],
        ];

        foreach (['first_name', 'last_name'] as $campo) {
            if ($payload['payer'][$campo] === '') {
                unset($payload['payer'][$campo]);
            }
        }

        $webhook = MercadoPago::notificationUrl();
        if ($webhook !== null) {
            $payload['notification_url'] = $webhook;
        }

        return $payload;
    }

    private function mensagemCartao(string $status, string $detalhe): string
    {
        if ($status === 'approved') {
            return 'Pagamento aprovado! Seu acesso foi liberado.';
        }
        if ($status === 'in_process' || $status === 'pending') {
            return 'Pagamento em análise. Seu acesso será liberado assim que for aprovado.';
        }

        return match ($detalhe) {
            'cc_rejected_insufficient_amount' => 'Saldo ou limite insuficiente. Tente outro cartão.',
            'cc_rejected_bad_filled_card_number', 'cc_rejected_bad_filled_date',
            'cc_rejected_bad_filled_security_code', 'cc_rejected_bad_filled_other'
                => 'Algum dado do cartão está incorreto. Confira e tente de novo.',
            'cc_rejected_call_for_authorize' => 'Seu banco pede autorização. Ligue para a operadora e tente de novo.',
            'cc_rejected_card_disabled' => 'Cartão desabilitado. Ative-o com o banco ou use outro.',
            'cc_rejected_duplicated_payment' => 'Você já fez um pagamento igual há pouco. Aguarde e confira seu histórico.',
            'cc_rejected_high_risk' => 'O pagamento foi recusado por segurança. Tente PIX ou outro cartão.',
            default => 'Pagamento recusado pelo banco. Tente outro cartão ou pague com PIX.',
        };
    }

    private function cpfValido(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * (($t + 1) - $i);
            }
            if ((int) $cpf[$t] !== ((10 * $soma) % 11) % 10) {
                return false;
            }
        }

        return true;
    }

    private function exigirProfessor(): void
    {
        if ($this->professor()['role'] !== User::ROLE_PROFESSOR) {
            $this->redirect('/');
        }
    }
}
