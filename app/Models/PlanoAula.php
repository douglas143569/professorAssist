<?php

namespace App\Models;

use App\Core\Model;

class PlanoAula extends Model
{
    public function byModulo(int $moduloId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM planos_aula WHERE modulo_id = :m ORDER BY id DESC'
        );
        $stmt->execute(['m' => $moduloId]);
        return $stmt->fetchAll();
    }

    /** Busca um plano garantindo o dono (via modulo -> disciplina). */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, m.id AS modulo_id, m.titulo AS modulo_titulo,
                    d.nome AS disciplina_nome
               FROM planos_aula p
               JOIN modulos m ON m.id = p.modulo_id
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE p.id = :id AND d.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO planos_aula (modulo_id, titulo, duracao, corpo, origem, status)
             VALUES (:m, :titulo, :duracao, :corpo, :origem, :status)'
        );
        $stmt->execute([
            'm' => $data['modulo_id'],
            'titulo' => $data['titulo'],
            'duracao' => ($data['duracao'] ?? '') !== '' ? $data['duracao'] : null,
            'corpo' => $data['corpo'] ?? null,
            'origem' => $data['origem'] ?? 'manual',
            'status' => $data['status'] ?? 'rascunho',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE planos_aula SET titulo = :titulo, duracao = :duracao,
                    corpo = :corpo, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'titulo' => $data['titulo'],
            'duracao' => ($data['duracao'] ?? '') !== '' ? $data['duracao'] : null,
            'corpo' => $data['corpo'],
            'status' => $data['status'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM planos_aula WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
               FROM planos_aula p
               JOIN modulos m ON m.id = p.modulo_id
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE d.user_id = :u'
        );
        $stmt->execute(['u' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
