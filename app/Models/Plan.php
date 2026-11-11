<?php

namespace App\Models;

use App\Core\Model;

class Plan extends Model
{
    public function active(): array
    {
        return $this->db->query('SELECT id, name, price, duration_days FROM plans WHERE active = 1 ORDER BY duration_days')
            ->fetchAll();
    }

    public function findActive(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, price, duration_days FROM plans WHERE id = :id AND active = 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }
}
