<?php

namespace App\Models;

use App\Core\Model;

class Atividade extends Model
{
    public function byModulo(int $moduloId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM atividades WHERE modulo_id = :m ORDER BY id DESC'
        );
        $stmt->execute(['m' => $moduloId]);
        return $stmt->fetchAll();
    }

    /** Busca uma atividade garantindo o dono (via modulo -> disciplina). */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, m.id AS modulo_id, m.titulo AS modulo_titulo,
                    d.nome AS disciplina_nome
               FROM atividades a
               JOIN modulos m ON m.id = a.modulo_id
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE a.id = :id AND d.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO atividades (modulo_id, titulo, descricao, formato, duracao, origem, status)
             VALUES (:m, :titulo, :descricao, :formato, :duracao, :origem, :status)'
        );
        $stmt->execute([
            'm' => $data['modulo_id'],
            'titulo' => $data['titulo'],
            'descricao' => ($data['descricao'] ?? '') !== '' ? $data['descricao'] : null,
            'formato' => ($data['formato'] ?? '') !== '' ? $data['formato'] : null,
            'duracao' => ($data['duracao'] ?? '') !== '' ? $data['duracao'] : null,
            'origem' => $data['origem'] ?? 'manual',
            'status' => $data['status'] ?? 'rascunho',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE atividades SET titulo = :titulo, descricao = :descricao,
                    formato = :formato, duracao = :duracao, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'titulo' => $data['titulo'],
            'descricao' => ($data['descricao'] ?? '') !== '' ? $data['descricao'] : null,
            'formato' => ($data['formato'] ?? '') !== '' ? $data['formato'] : null,
            'duracao' => ($data['duracao'] ?? '') !== '' ? $data['duracao'] : null,
            'status' => $data['status'],
        ]);
    }

    public function aprovar(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE atividades SET status = "aprovado" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM atividades WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
               FROM atividades a
               JOIN modulos m ON m.id = a.modulo_id
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE d.user_id = :u'
        );
        $stmt->execute(['u' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
