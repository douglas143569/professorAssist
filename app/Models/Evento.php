<?php

namespace App\Models;

use App\Core\Model;

class Evento extends Model
{
    /** Eventos de um mes (com nome da disciplina), para a grade. */
    public function byMonth(int $userId, int $ano, int $mes): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, d.nome AS disciplina_nome
               FROM eventos e
               LEFT JOIN disciplinas d ON d.id = e.disciplina_id
              WHERE e.user_id = :u
                AND YEAR(e.data_evento) = :ano
                AND MONTH(e.data_evento) = :mes
              ORDER BY e.data_evento, e.hora IS NULL, e.hora'
        );
        $stmt->execute(['u' => $userId, 'ano' => $ano, 'mes' => $mes]);
        return $stmt->fetchAll();
    }

    /** Proximos eventos pendentes (a partir de hoje). */
    public function proximos(int $userId, int $limit = 8): array
    {
        $limit = max(1, min($limit, 50));
        $stmt = $this->db->prepare(
            "SELECT e.*, d.nome AS disciplina_nome
               FROM eventos e
               LEFT JOIN disciplinas d ON d.id = e.disciplina_id
              WHERE e.user_id = :u AND e.concluido = 0 AND e.data_evento >= CURDATE()
              ORDER BY e.data_evento, e.hora IS NULL, e.hora
              LIMIT {$limit}"
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM eventos WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO eventos (user_id, disciplina_id, titulo, tipo, descricao, data_evento, hora)
             VALUES (:u, :disc, :titulo, :tipo, :descricao, :data, :hora)'
        );
        $stmt->execute([
            'u' => $data['user_id'],
            'disc' => !empty($data['disciplina_id']) ? (int) $data['disciplina_id'] : null,
            'titulo' => $data['titulo'],
            'tipo' => $data['tipo'],
            'descricao' => ($data['descricao'] ?? '') !== '' ? $data['descricao'] : null,
            'data' => $data['data_evento'],
            'hora' => ($data['hora'] ?? '') !== '' ? $data['hora'] : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function toggleConcluido(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE eventos SET concluido = IF(concluido = 1, 0, 1) WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM eventos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
