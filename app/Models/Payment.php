<?php

namespace App\Models;

use App\Core\Model;

class Payment extends Model
{
    public function createPending(
        int $userId,
        int $subscriptionId,
        float $amount,
        ?int $promotionId = null
    ): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO payments (user_id, subscription_id, amount, promotion_id, status)
             VALUES (:user_id, :subscription_id, :amount, :promotion_id, 'pending')"
        );
        $stmt->execute([
            'user_id' => $userId,
            'subscription_id' => $subscriptionId,
            'amount' => number_format($amount, 2, '.', ''),
            'promotion_id' => $promotionId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function setCheckoutPreference(int $paymentId, string $preferenceId, string $checkoutUrl): void
    {
        $stmt = $this->db->prepare(
            'UPDATE payments SET checkout_preference_id = :preference, checkout_url = :checkout_url WHERE id = :id'
        );
        $stmt->execute([
            'preference' => $preferenceId,
            'checkout_url' => $checkoutUrl,
            'id' => $paymentId,
        ]);
    }

    public function failCheckout(int $paymentId): void
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'SELECT promotion_id, subscription_id FROM payments WHERE id = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $paymentId]);
            $payment = $stmt->fetch();
            if (!$payment) {
                $this->db->commit();
                return;
            }

            if ($payment['promotion_id'] !== null) {
                $this->db->prepare(
                    "UPDATE user_promotions
                        SET status = 'available', reserved_payment_id = NULL
                      WHERE id = :promotion_id AND status = 'reserved' AND reserved_payment_id = :payment_id"
                )->execute(['promotion_id' => $payment['promotion_id'], 'payment_id' => $paymentId]);
            }

            $this->db->prepare(
                "UPDATE payments SET status = 'checkout_error', promotion_id = NULL WHERE id = :id AND status = 'pending'"
            )->execute(['id' => $paymentId]);
            $this->db->prepare(
                "UPDATE subscriptions SET status = 'cancelled' WHERE id = :id AND status = 'pending'"
            )->execute(['id' => $payment['subscription_id']]);

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function recordProviderStatus(
        int $paymentId,
        string $providerPaymentId,
        string $status,
        ?string $method
    ): void {
        $stmt = $this->db->prepare(
            'UPDATE payments
                SET transaction_id = :transaction_id, status = :status, payment_method = :method,
                    approved_at = COALESCE(approved_at, NOW())
              WHERE id = :id'
        );
        $stmt->execute([
            'transaction_id' => $providerPaymentId,
            'status' => substr($status, 0, 30),
            'method' => $method === null ? null : substr($method, 0, 40),
            'id' => $paymentId,
        ]);
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT pay.amount, pay.payment_method, pay.transaction_id, pay.status, pay.created_at,
                    p.name AS plan_name
               FROM payments pay
               JOIN subscriptions s ON s.id = pay.subscription_id
               JOIN plans p ON p.id = s.plan_id
              WHERE pay.user_id = :user_id
              ORDER BY pay.created_at DESC LIMIT 50'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function findByExternalReference(int $paymentId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT pay.id, pay.user_id, pay.amount, pay.status, s.id AS subscription_id
               FROM payments pay
               JOIN subscriptions s ON s.id = pay.subscription_id
              WHERE pay.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $paymentId]);

        return $stmt->fetch() ?: null;
    }

    public function findForUser(int $paymentId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, status, transaction_id FROM payments WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['id' => $paymentId, 'user_id' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public function setNonApprovedStatus(int $paymentId, string $status, string $providerPaymentId, ?string $method): void
    {
        $allowed = ['pending', 'rejected', 'cancelled', 'refunded', 'charged_back', 'in_process'];
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT promotion_id FROM payments WHERE id = :id AND status <> 'approved' FOR UPDATE"
            );
            $stmt->execute(['id' => $paymentId]);
            $promotionId = $stmt->fetchColumn();

            $this->db->prepare(
                "UPDATE payments
                    SET transaction_id = :transaction_id, status = :status, payment_method = :method
                  WHERE id = :id AND status <> 'approved'"
            )->execute([
                'transaction_id' => $providerPaymentId,
                'status' => $status,
                'method' => $method === null ? null : substr($method, 0, 40),
                'id' => $paymentId,
            ]);

            if ($promotionId && in_array($status, ['rejected', 'cancelled'], true)) {
                $this->db->prepare(
                    "UPDATE user_promotions
                        SET status = 'available', reserved_payment_id = NULL
                      WHERE id = :promotion_id AND status = 'reserved' AND reserved_payment_id = :payment_id"
                )->execute(['promotion_id' => $promotionId, 'payment_id' => $paymentId]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function adminSummary(): array
    {
        return $this->db->query(
            "SELECT
                (SELECT COUNT(*) FROM users WHERE role = 'professor') AS customers,
                (SELECT COUNT(*) FROM subscriptions WHERE status = 'active' AND ends_at > NOW()) AS active_subscriptions,
                (SELECT COUNT(*) FROM subscriptions WHERE status = 'expired' OR (status = 'active' AND ends_at <= NOW())) AS expired_subscriptions,
                (SELECT COALESCE(SUM(amount), 0) FROM payments
                  WHERE status = 'approved' AND approved_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')) AS monthly_revenue,
                (SELECT COALESCE(SUM(amount), 0) FROM payments
                  WHERE status = 'approved' AND YEAR(approved_at) = YEAR(CURRENT_DATE)) AS yearly_revenue"
        )->fetch();
    }

    public function adminHistory(): array
    {
        return $this->db->query(
            'SELECT pay.id, pay.amount, pay.payment_method, pay.transaction_id, pay.status, pay.created_at,
                    u.name AS user_name, u.email, p.name AS plan_name
               FROM payments pay
               JOIN users u ON u.id = pay.user_id
               JOIN subscriptions s ON s.id = pay.subscription_id
               JOIN plans p ON p.id = s.plan_id
              ORDER BY pay.created_at DESC LIMIT 200'
        )->fetchAll();
    }
}
