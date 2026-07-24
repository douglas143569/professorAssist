<?php

namespace App\Models;

use App\Core\Model;

class Modulo extends Model
{
    public function byDisciplina(int $disciplinaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM modulos WHERE disciplina_id = :d ORDER BY ordem, id'
        );
        $stmt->execute(['d' => $disciplinaId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca um modulo garantindo o dono (via disciplina). Traz dados da
     * disciplina que a tela e a IA precisam (nome, etapa).
     */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, d.nome AS disciplina_nome, d.etapa AS disciplina_etapa
               FROM modulos m
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE m.id = :id AND d.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO modulos (disciplina_id, titulo, ordem, objetivos, codigos_bncc)
             VALUES (:d, :titulo, :ordem, :obj, :bncc)'
        );
        $stmt->execute([
            'd' => $data['disciplina_id'],
            'titulo' => $data['titulo'],
            'ordem' => (int) ($data['ordem'] ?? 0),
            'obj' => ($data['objetivos'] ?? '') !== '' ? $data['objetivos'] : null,
            'bncc' => ($data['codigos_bncc'] ?? '') !== '' ? $data['codigos_bncc'] : null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
