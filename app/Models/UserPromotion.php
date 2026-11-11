<?php

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class UserPromotion extends Model
{
    public function create(int $userId, int $planId, float $price, string $expiresAt, int $adminId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_promotions (user_id, plan_id, promotional_price, expires_at, created_by)
             VALUES (:user_id, :plan_id, :price, :expires_at, :admin_id)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'plan_id' => $planId,
            'price' => number_format($price, 2, '.', ''),
            'expires_at' => $expiresAt,
            'admin_id' => $adminId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT promo.id, promo.plan_id, promo.promotional_price, promo.expires_at, promo.status,
                    plan.name AS plan_name, pay.checkout_url
               FROM user_promotions promo
               JOIN plans plan ON plan.id = promo.plan_id
               LEFT JOIN payments pay ON pay.id = promo.reserved_payment_id
              WHERE promo.user_id = :user_id AND promo.status IN ('available', 'reserved')
                AND (promo.status = 'reserved' OR promo.expires_at > NOW())
              ORDER BY promo.expires_at, promo.id"
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function allForAdmin(): array
    {
        return $this->db->query(
            'SELECT promo.id, promo.promotional_price, promo.expires_at, promo.status,
                    u.name AS user_name, u.email, plan.name AS plan_name
               FROM user_promotions promo
               JOIN users u ON u.id = promo.user_id
               JOIN plans plan ON plan.id = promo.plan_id
              ORDER BY promo.created_at DESC LIMIT 200'
        )->fetchAll();
    }

    public function reserveCheckout(int $userId, int $planId, int $promotionId): array
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT promotional_price FROM user_promotions
                  WHERE id = :id AND user_id = :user_id AND plan_id = :plan_id
                    AND status = 'available' AND expires_at > NOW()
                  FOR UPDATE"
            );
            $stmt->execute(['id' => $promotionId, 'user_id' => $userId, 'plan_id' => $planId]);
            $price = $stmt->fetchColumn();

            if ($price === false) {
                throw new RuntimeException('Esta promoção não está mais disponível para sua conta.');
            }

            $subscriptionId = (new Subscription())->createPending($userId, $planId);
            $paymentId = (new Payment())->createPending($userId, $subscriptionId, (float) $price, $promotionId);
            $this->db->prepare(
                "UPDATE user_promotions
                    SET status = 'reserved', reserved_payment_id = :payment_id
                  WHERE id = :id AND status = 'available'"
            )->execute(['payment_id' => $paymentId, 'id' => $promotionId]);

            $this->db->commit();

            return ['subscription_id' => $subscriptionId, 'payment_id' => $paymentId, 'price' => (float) $price];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function redeemForPayment(int $promotionId, int $paymentId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE user_promotions
                SET status = 'redeemed', redeemed_at = NOW()
              WHERE id = :id AND status = 'reserved' AND reserved_payment_id = :payment_id"
        );
        $stmt->execute(['id' => $promotionId, 'payment_id' => $paymentId]);

        if ($stmt->rowCount() !== 1) {
            $check = $this->db->prepare(
                'SELECT status, reserved_payment_id FROM user_promotions WHERE id = :id'
            );
            $check->execute(['id' => $promotionId]);
            $row = $check->fetch();
            if (!$row || $row['status'] !== 'redeemed' || (int) $row['reserved_payment_id'] !== $paymentId) {
                throw new RuntimeException('Não foi possível confirmar o uso da promoção.');
            }
        }
    }
}
