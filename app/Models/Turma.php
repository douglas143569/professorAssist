<?php

namespace App\Models;

use App\Core\Model;

class Turma extends Model
{
    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, e.nome AS escola_nome,
                    (SELECT COUNT(*) FROM disciplinas d WHERE d.turma_id = t.id) AS n_materias
               FROM turmas t
               LEFT JOIN escolas e ON e.id = t.escola_id
              WHERE t.user_id = :u
              ORDER BY e.nome, t.nome'
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }

    /** Turmas de uma escola. */
    public function byEscola(int $escolaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*,
                    (SELECT COUNT(*) FROM disciplinas d WHERE d.turma_id = t.id) AS n_materias
               FROM turmas t
              WHERE t.escola_id = :e
              ORDER BY t.nome'
        );
        $stmt->execute(['e' => $escolaId]);
        return $stmt->fetchAll();
    }

    /** Busca uma turma garantindo o dono; traz o nome da escola (breadcrumb). */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, e.nome AS escola_nome
               FROM turmas t
               LEFT JOIN escolas e ON e.id = t.escola_id
              WHERE t.id = :id AND t.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO turmas (user_id, escola_id, nome, etapa, ano_serie)
             VALUES (:u, :escola, :nome, :etapa, :ano)'
        );
        $stmt->execute([
            'u' => $data['user_id'],
            'escola' => $data['escola_id'],
            'nome' => $data['nome'],
            'etapa' => $data['etapa'],
            'ano' => ($data['ano_serie'] ?? '') !== '' ? $data['ano_serie'] : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        // Materias, temas e conteudos caem por ON DELETE CASCADE.
        $stmt = $this->db->prepare('DELETE FROM turmas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM turmas WHERE user_id = :u');
        $stmt->execute(['u' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
