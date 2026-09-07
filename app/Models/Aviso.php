<?php

namespace App\Models;

use App\Core\Model;

/**
 * Mural de avisos da turma. Professor publica, aluno so le.
 */
class Aviso extends Model
{
    public function criar(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO avisos (turma_id, user_id, titulo, corpo, fixado)
             VALUES (:turma, :user, :titulo, :corpo, :fixado)'
        );
        $stmt->execute([
            'turma' => $d['turma_id'],
            'user' => $d['user_id'],
            'titulo' => $d['titulo'],
            'corpo' => $d['corpo'],
            'fixado' => !empty($d['fixado']) ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Avisos de uma turma (fixados primeiro, depois mais recentes). */
    public function daTurma(int $turmaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM avisos WHERE turma_id = :t ORDER BY fixado DESC, created_at DESC'
        );
        $stmt->execute(['t' => $turmaId]);

        return $stmt->fetchAll();
    }

    /** Avisos das turmas do aluno, com o nome da turma. */
    public function paraAluno(int $alunoId, bool $somenteFixados = false): array
    {
        $sql =
            'SELECT a.*, t.nome AS turma_nome
               FROM turma_alunos ta
               JOIN turmas t ON t.id = ta.turma_id
               JOIN avisos a ON a.turma_id = t.id
              WHERE ta.aluno_id = :a AND ta.situacao = \'ativa\''
            . ($somenteFixados ? ' AND a.fixado = 1' : '')
            . ' ORDER BY a.fixado DESC, a.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['a' => $alunoId]);

        return $stmt->fetchAll();
    }

    /** Aviso garantindo que a turma pertence ao professor. */
    public function findDoProfessor(int $id, int $profId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, a.turma_id
               FROM avisos a
               JOIN turmas t ON t.id = a.turma_id
              WHERE a.id = :id AND t.user_id = :u
              LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'u' => $profId]);

        return $stmt->fetch() ?: null;
    }

    public function excluir(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM avisos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
