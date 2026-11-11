<?php

namespace App\Models;

use App\Core\Model;

class Subscription extends Model
{
    public function createPending(int $userId, int $planId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO subscriptions (user_id, plan_id, status)
             VALUES (:user_id, :plan_id, 'pending')"
        );
        $stmt->execute(['user_id' => $userId, 'plan_id' => $planId]);

        return (int) $this->db->lastInsertId();
    }

    /** Nome do plano interno de teste (ver migration 0042). */
    public const TRIAL_PLAN_NAME = 'Teste grátis';

    /**
     * Inicia o teste gratis de 3 dias. So concede se o usuario NUNCA teve
     * assinatura (um teste por conta). Retorna true se concedeu.
     */
    public function startTrial(int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT id, duration_days FROM plans WHERE name = :n LIMIT 1');
        $stmt->execute(['n' => self::TRIAL_PLAN_NAME]);
        $plan = $stmt->fetch();

        if (!$plan) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT 1 FROM subscriptions WHERE user_id = :u LIMIT 1');
        $stmt->execute(['u' => $userId]);

        if ($stmt->fetchColumn()) {
            return false;
        }

        $inicio = new \DateTimeImmutable('now');
        $fim = $inicio->modify('+' . (int) $plan['duration_days'] . ' days');

        $this->db->prepare(
            "INSERT INTO subscriptions (user_id, plan_id, starts_at, ends_at, status)
             VALUES (:u, :p, :s, :e, 'active')"
        )->execute([
            'u' => $userId,
            'p' => (int) $plan['id'],
            's' => $inicio->format('Y-m-d H:i:s'),
            'e' => $fim->format('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function currentForUser(int $userId): ?array
    {
        $this->db->prepare(
            "UPDATE subscriptions SET status = 'expired'
              WHERE user_id = :user_id AND status = 'active' AND ends_at <= NOW()"
        )->execute(['user_id' => $userId]);

        $stmt = $this->db->prepare(
            "SELECT s.id, s.user_id, s.plan_id, s.starts_at, s.ends_at, s.status,
                    p.name AS plan_name, p.price, p.duration_days
               FROM subscriptions s
               JOIN plans p ON p.id = s.plan_id
              WHERE s.user_id = :user_id AND s.status = 'active' AND s.ends_at > NOW()
              ORDER BY s.ends_at DESC LIMIT 1"
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public function forPayment(int $paymentId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.id, s.user_id, s.plan_id, s.starts_at, s.ends_at, s.status,
                    p.name AS plan_name, p.price, p.duration_days
               FROM payments pay
               JOIN subscriptions s ON s.id = pay.subscription_id
               JOIN plans p ON p.id = s.plan_id
              WHERE pay.id = :payment_id LIMIT 1'
        );
        $stmt->execute(['payment_id' => $paymentId]);

        return $stmt->fetch() ?: null;
    }

    public function activatePaid(int $paymentId): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'SELECT pay.status AS payment_status, pay.amount, pay.promotion_id, s.id, s.user_id,
                        s.status AS subscription_status, p.duration_days
                   FROM payments pay
                   JOIN subscriptions s ON s.id = pay.subscription_id
                   JOIN plans p ON p.id = s.plan_id
                  WHERE pay.id = :payment_id FOR UPDATE'
            );
            $stmt->execute(['payment_id' => $paymentId]);
            $row = $stmt->fetch();

            if (!$row) {
                $this->db->commit();
                return false;
            }

            if ($row['payment_status'] === 'approved' && $row['subscription_status'] === 'active') {
                $this->db->commit();
                return true;
            }

            $startsAt = new \DateTimeImmutable('now');
            $periodBase = $startsAt;
            $stmt = $this->db->prepare(
                "SELECT ends_at FROM subscriptions
                  WHERE user_id = :user_id AND status = 'active' AND ends_at > NOW()
                  ORDER BY ends_at DESC LIMIT 1 FOR UPDATE"
            );
            $stmt->execute(['user_id' => $row['user_id']]);
            $existingEnd = $stmt->fetchColumn();
            if ($existingEnd) {
                $periodBase = new \DateTimeImmutable($existingEnd);
            }
            $end = $periodBase->modify('+' . (int) $row['duration_days'] . ' days');

            $this->db->prepare(
                "UPDATE subscriptions SET status = 'cancelled'
                  WHERE user_id = :user_id AND status = 'active'"
            )->execute(['user_id' => $row['user_id']]);

            $this->db->prepare(
                "UPDATE subscriptions
                    SET status = 'active', starts_at = :starts_at, ends_at = :ends_at
                  WHERE id = (SELECT subscription_id FROM payments WHERE id = :payment_id)"
            )->execute([
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'ends_at' => $end->format('Y-m-d H:i:s'),
                'payment_id' => $paymentId,
            ]);

            $this->db->prepare("UPDATE payments SET status = 'approved' WHERE id = :id")
                ->execute(['id' => $paymentId]);

            if ($row['promotion_id'] !== null) {
                (new UserPromotion())->redeemForPayment((int) $row['promotion_id'], $paymentId);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
