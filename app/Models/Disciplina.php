<?php

namespace App\Models;

use App\Core\Model;

class Disciplina extends Model
{
    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM disciplinas WHERE user_id = :u ORDER BY nome'
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }

    /** Busca uma disciplina garantindo que pertence ao professor. */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM disciplinas WHERE id = :id AND user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM disciplinas WHERE user_id = :u');
        $stmt->execute(['u' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO disciplinas (user_id, nome, etapa, ano_serie)
             VALUES (:u, :nome, :etapa, :ano)'
        );
        $stmt->execute([
            'u' => $data['user_id'],
            'nome' => $data['nome'],
            'etapa' => $data['etapa'],
            'ano' => ($data['ano_serie'] ?? '') !== '' ? $data['ano_serie'] : null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
