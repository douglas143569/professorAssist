<?php

namespace App\Models;

use App\Core\Model;

class UserAccessOverride extends Model
{
    public function forUser(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT mode, access_until
               FROM user_access_overrides
              WHERE user_id = :user_id
                AND (mode = \'blocked\' OR access_until > NOW())'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public function setGrant(int $userId, int $days, int $adminId): void
    {
        $until = (new \DateTimeImmutable())->modify('+' . $days . ' days')->format('Y-m-d H:i:s');
        $this->save($userId, 'granted', $until, $adminId);
    }

    public function setBlocked(int $userId, int $adminId): void
    {
        $this->save($userId, 'blocked', null, $adminId);
    }

    private function save(int $userId, string $mode, ?string $until, int $adminId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_access_overrides (user_id, mode, access_until, updated_by)
             VALUES (:user_id, :mode, :access_until, :admin_id)
             ON DUPLICATE KEY UPDATE mode = VALUES(mode), access_until = VALUES(access_until),
                                     updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'user_id' => $userId,
            'mode' => $mode,
            'access_until' => $until,
            'admin_id' => $adminId,
        ]);
    }

    public function professorsForAdmin(): array
    {
        return $this->db->query(
            "SELECT u.id, u.name, u.email, u.ativo,
                    ov.mode AS access_mode, ov.access_until,
                    s.status AS subscription_status, s.ends_at AS subscription_ends_at,
                    p.name AS plan_name
               FROM users u
               LEFT JOIN user_access_overrides ov ON ov.user_id = u.id
               LEFT JOIN subscriptions s ON s.id = (
                    SELECT s2.id FROM subscriptions s2
                     WHERE s2.user_id = u.id AND s2.status = 'active' AND s2.ends_at > NOW()
                     ORDER BY s2.ends_at DESC LIMIT 1
               )
               LEFT JOIN plans p ON p.id = s.plan_id
              WHERE u.role = 'professor'
              ORDER BY u.name"
        )->fetchAll();
    }

    public function findProfessor(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, email FROM users WHERE id = :id AND role = 'professor' LIMIT 1"
        );
        $stmt->execute(['id' => $userId]);

        return $stmt->fetch() ?: null;
    }
}
