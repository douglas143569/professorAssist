<?php

namespace App\Models;

use App\Core\Model;

/**
 * Materia: uma disciplina dentro de uma Turma (ex: Matematica).
 * (A tabela continua 'disciplinas' por compatibilidade com o restante.)
 */
class Disciplina extends Model
{
    /** Materias de uma turma. */
    public function byTurma(int $turmaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM disciplinas WHERE turma_id = :t ORDER BY nome'
        );
        $stmt->execute(['t' => $turmaId]);
        return $stmt->fetchAll();
    }

    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM disciplinas WHERE user_id = :u ORDER BY nome'
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }

    /** Busca uma materia garantindo o dono; traz dados da turma (breadcrumb). */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT d.*, t.nome AS turma_nome
               FROM disciplinas d
               LEFT JOIN turmas t ON t.id = d.turma_id
              WHERE d.id = :id AND d.user_id = :u'
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

    public function delete(int $id): void
    {
        // Temas (modulos) e o que houver dentro caem por ON DELETE CASCADE.
        $stmt = $this->db->prepare('DELETE FROM disciplinas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO disciplinas (user_id, turma_id, nome, etapa, ano_serie)
             VALUES (:u, :turma, :nome, :etapa, :ano)'
        );
        $stmt->execute([
            'u' => $data['user_id'],
            'turma' => $data['turma_id'] ?? null,
            'nome' => $data['nome'],
            'etapa' => $data['etapa'],
            'ano' => ($data['ano_serie'] ?? '') !== '' ? $data['ano_serie'] : null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
