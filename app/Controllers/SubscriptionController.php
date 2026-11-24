<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UserAccessOverride;
use App\Models\User;
use App\Models\UserPromotion;
use App\Services\Logger;
use App\Services\MercadoPago;
use App\Services\PaymentWebhook;
use Throwable;

class SubscriptionController extends AppController
{
    public function index(): void
    {
        $this->exigirProfessor();
        $user = $this->professor();
        $this->view('assinatura.index', [
            'title' => 'Minha assinatura',
            'subscription' => (new Subscription())->currentForUser((int) $user['id']),
            'accessOverride' => (new UserAccessOverride())->forUser((int) $user['id']),
            'plans' => (new Plan())->active(),
            'payments' => (new Payment())->forUser((int) $user['id']),
            'promotions' => (new UserPromotion())->forUser((int) $user['id']),
        ]);
    }

    public function checkout(): void
    {
        $this->exigirProfessor();
        $user = $this->professor();
        $planId = filter_var($_POST['plan_id'] ?? null, FILTER_VALIDATE_INT);
        $plan = $planId ? (new Plan())->findActive((int) $planId) : null;

        if ($plan === null) {
            $this->flash('Plano inválido ou indisponível. Atualize a página e tente novamente.');
            $this->redirect('/assinatura');
        }

        $hasPromotion = isset($_POST['promotion_id']) && $_POST['promotion_id'] !== '';
        $promotionId = $hasPromotion ? filter_var($_POST['promotion_id'], FILTER_VALIDATE_INT) : null;
        if ($hasPromotion && (!$promotionId || $promotionId < 1)) {
            $this->flash('A promoção selecionada é inválida.');
            $this->redirect('/assinatura');
        }
        $paymentId = null;
        $reservedPromotionId = null;
        try {
            if ($promotionId !== null) {
                $checkout = (new UserPromotion())->reserveCheckout(
                    (int) $user['id'],
                    (int) $plan['id'],
                    (int) $promotionId
                );
                $paymentId = (int) $checkout['payment_id'];
                $reservedPromotionId = (int) $promotionId;
                $plan['price'] = $checkout['price'];
            } else {
                $subscriptionId = (new Subscription())->createPending((int) $user['id'], (int) $plan['id']);
                $paymentId = (new Payment())->createPending(
                    (int) $user['id'],
                    $subscriptionId,
                    (float) $plan['price']
                );
            }

            $preference = (new MercadoPago())->createCheckoutPreference($plan, $user, $paymentId);
            (new Payment())->setCheckoutPreference(
                $paymentId,
                (string) $preference['id'],
                (string) $preference['init_point']
            );

            Logger::activity('payment.checkout_created', [
                'user_id' => (int) $user['id'],
                'entity_type' => 'payment',
                'entity_id' => $paymentId,
                'description' => 'Checkout criado para o plano ' . $plan['name'],
            ]);

            $this->redirect((string) $preference['init_point']);
        } catch (Throwable $e) {
            Logger::exception($e);
            if ($paymentId !== null) {
                (new Payment())->failCheckout($paymentId);
            }
            $this->flash('Não foi possível iniciar o pagamento. Tente novamente mais tarde.');
            if ($reservedPromotionId !== null) {
                $this->flash('O checkout falhou; a promoção continua disponível. Tente novamente.');
            }
            $this->redirect('/assinatura');
        }
    }

    public function retorno(): void
    {
        $this->exigirProfessor();
        $resultado = (string) ($_GET['resultado'] ?? '');

        // O Mercado Pago devolve payment_id na volta do checkout. Sincronizamos na hora
        // (consultando a API, nunca confiando na URL) — útil quando o webhook não alcança
        // o servidor, como em testes locais. O webhook continua sendo a fonte principal.
        $providerPaymentId = (string) ($_GET['payment_id'] ?? $_GET['collection_id'] ?? '');
        if ($providerPaymentId !== '' && ctype_digit($providerPaymentId)) {
            try {
                (new PaymentWebhook())->handle($providerPaymentId, (int) $this->professor()['id']);
            } catch (Throwable $e) {
                Logger::exception($e);
            }
        }
        $mensagem = match ($resultado) {
            'sucesso' => 'Recebemos o retorno do checkout. Seu acesso será liberado após a confirmação segura do pagamento.',
            'pendente' => 'Seu pagamento está pendente. O acesso será liberado assim que o Mercado Pago confirmar.',
            'falha' => 'O pagamento não foi concluído. Você pode tentar novamente.',
            default => 'Aguardando confirmação do pagamento.',
        };
        $this->flash($mensagem);
        $this->redirect('/assinatura');
    }

    private function exigirProfessor(): void
    {
        if ($this->professor()['role'] !== User::ROLE_PROFESSOR) {
            $this->redirect('/');
        }
    }

}
