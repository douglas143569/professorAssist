<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\UserAccessOverride;
use App\Models\UserPromotion;
use App\Services\Logger;
use DateTimeImmutable;

class AdminBillingController extends AppController
{
    public function index(): void
    {
        $this->exigirAdmin();
        $payments = new Payment();
        $this->view('assinatura.admin', [
            'title' => 'Financeiro',
            'summary' => $payments->adminSummary(),
            'payments' => $payments->adminHistory(),
            'professors' => (new UserAccessOverride())->professorsForAdmin(),
            'plans' => (new Plan())->active(),
            'promotions' => (new UserPromotion())->allForAdmin(),
        ]);
    }

    public function liberarAcesso(): void
    {
        $this->exigirAdmin();
        $user = $this->targetProfessor();
        $days = filter_var($_POST['days'] ?? null, FILTER_VALIDATE_INT);

        if ($days === false || $days < 1 || $days > 3650) {
            $this->voltarFinanceiro('Informe um período de 1 a 3650 dias para liberar o acesso.');
        }

        (new UserAccessOverride())->setGrant((int) $user['id'], $days, (int) $this->professor()['id']);
        Logger::activity('billing.access_granted', [
            'user_id' => (int) $user['id'],
            'entity_type' => 'user',
            'entity_id' => (int) $user['id'],
            'description' => "Acesso liberado pelo administrador por {$days} dias.",
            'properties' => ['admin_user_id' => (int) $this->professor()['id'], 'duration_days' => $days],
        ]);
        $this->voltarFinanceiro("Acesso liberado para {$user['name']} por {$days} dias.");
    }

    public function bloquearAcesso(): void
    {
        $this->exigirAdmin();
        $user = $this->targetProfessor();

        (new UserAccessOverride())->setBlocked((int) $user['id'], (int) $this->professor()['id']);
        Logger::activity('billing.access_blocked', [
            'user_id' => (int) $user['id'],
            'entity_type' => 'user',
            'entity_id' => (int) $user['id'],
            'description' => 'Acesso bloqueado manualmente pelo administrador.',
            'properties' => ['admin_user_id' => (int) $this->professor()['id']],
        ]);
        $this->voltarFinanceiro("Acesso de {$user['name']} bloqueado, inclusive se houver assinatura paga.");
    }

    public function criarPromocao(): void
    {
        $this->exigirAdmin();
        $user = $this->targetProfessor();
        $planId = filter_var($_POST['plan_id'] ?? null, FILTER_VALIDATE_INT);
        $plan = $planId ? (new Plan())->findActive((int) $planId) : null;
        $rawPriceInput = $_POST['price'] ?? '';
        $expiresInput = $_POST['expires_on'] ?? '';
        $rawPrice = is_string($rawPriceInput) ? trim($rawPriceInput) : '';
        $expiresInput = is_string($expiresInput) ? trim($expiresInput) : '';

        if ($plan === null || !preg_match('/^\d{1,6}(?:[.,]\d{1,2})?$/', $rawPrice)) {
            $this->voltarFinanceiro('Selecione um plano e informe um preço válido para a promoção.');
        }

        $price = (float) str_replace(',', '.', $rawPrice);
        if ($price <= 0 || $price >= (float) $plan['price']) {
            $this->voltarFinanceiro('O preço promocional deve ser maior que zero e menor que o preço normal.');
        }

        $expires = DateTimeImmutable::createFromFormat('!Y-m-d', $expiresInput);
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (!$expires || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $expires->setTime(23, 59, 59) <= new DateTimeImmutable()) {
            $this->voltarFinanceiro('Informe uma data de validade futura para a promoção.');
        }

        (new UserPromotion())->create(
            (int) $user['id'],
            (int) $plan['id'],
            $price,
            $expires->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
            (int) $this->professor()['id']
        );
        Logger::activity('billing.promotion_created', [
            'user_id' => (int) $user['id'],
            'entity_type' => 'user',
            'entity_id' => (int) $user['id'],
            'description' => "Promoção individual criada para o plano {$plan['name']}.",
            'properties' => [
                'admin_user_id' => (int) $this->professor()['id'],
                'plan_id' => (int) $plan['id'],
                'promotional_price' => $price,
                'expires_at' => $expires->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
            ],
        ]);
        $this->voltarFinanceiro("Promoção criada para {$user['name']} no plano {$plan['name']}.");
    }

    private function targetProfessor(): array
    {
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $user = $userId ? (new UserAccessOverride())->findProfessor((int) $userId) : null;
        if ($user === null) {
            $this->voltarFinanceiro('Selecione uma conta de professor válida.');
        }

        return $user;
    }

    private function voltarFinanceiro(string $message): never
    {
        $this->flash($message);
        $this->redirect('/admin/financeiro');
    }
}
