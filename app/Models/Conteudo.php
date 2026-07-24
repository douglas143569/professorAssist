<?php

namespace App\Models;

use App\Core\Model;

class Conteudo extends Model
{
    public function byModulo(int $moduloId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM conteudos WHERE modulo_id = :m ORDER BY id DESC'
        );
        $stmt->execute(['m' => $moduloId]);
        return $stmt->fetchAll();
    }

    /** Busca um conteudo garantindo o dono (via modulo -> disciplina). */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, m.id AS modulo_id, m.titulo AS modulo_titulo,
                    d.nome AS disciplina_nome
               FROM conteudos c
               JOIN modulos m ON m.id = c.modulo_id
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE c.id = :id AND d.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
               FROM conteudos c
               JOIN modulos m ON m.id = c.modulo_id
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE d.user_id = :u'
        );
        $stmt->execute(['u' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO conteudos (modulo_id, titulo, corpo, origem, status)
             VALUES (:m, :titulo, :corpo, :origem, :status)'
        );
        $stmt->execute([
            'm' => $data['modulo_id'],
            'titulo' => $data['titulo'],
            'corpo' => $data['corpo'] ?? null,
            'origem' => $data['origem'] ?? 'manual',
            'status' => $data['status'] ?? 'rascunho',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE conteudos SET titulo = :titulo, corpo = :corpo, status = :status
              WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'titulo' => $data['titulo'],
            'corpo' => $data['corpo'],
            'status' => $data['status'],
        ]);
    }
}
