<?php

namespace App\Models;

use App\Core\Model;

/**
 * Matricula do aluno numa turma (tabela turma_alunos).
 *
 * E o roster da turma -- a mesma lista que servira, mais adiante, para a
 * chamada/presenca. Um aluno pode estar em mais de uma turma.
 */
class Matricula extends Model
{
    /** Cria a matricula. Idempotente: ignora se ja existir (UNIQUE turma+aluno). */
    public function inscrever(int $turmaId, int $alunoId): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO turma_alunos (turma_id, aluno_id) VALUES (:t, :a)'
        );
        $stmt->execute(['t' => $turmaId, 'a' => $alunoId]);
    }

    /** Alunos de uma turma, com dados da matricula. */
    public function daTurma(int $turmaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ta.id AS matricula_id, ta.situacao, ta.created_at AS matriculado_em,
                    u.id AS aluno_id, u.name AS aluno_nome, u.last_login_at
               FROM turma_alunos ta
               JOIN users u ON u.id = ta.aluno_id
              WHERE ta.turma_id = :t
              ORDER BY u.name'
        );
        $stmt->execute(['t' => $turmaId]);

        return $stmt->fetchAll();
    }

    /** Turmas em que o aluno esta matriculado (ativas), com nome da escola. */
    public function turmasDoAluno(int $alunoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.id, t.nome, t.etapa, t.ano_serie,
                    e.nome AS escola_nome
               FROM turma_alunos ta
               JOIN turmas t ON t.id = ta.turma_id
          LEFT JOIN escolas e ON e.id = t.escola_id
              WHERE ta.aluno_id = :a AND ta.situacao = :sit
              ORDER BY e.nome, t.nome'
        );
        $stmt->execute(['a' => $alunoId, 'sit' => 'ativa']);

        return $stmt->fetchAll();
    }

    /** Ids das turmas ativas do aluno (para checagem de acesso). */
    public function turmaIdsDoAluno(int $alunoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT turma_id FROM turma_alunos WHERE aluno_id = :a AND situacao = :sit'
        );
        $stmt->execute(['a' => $alunoId, 'sit' => 'ativa']);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** Ja existe um aluno com esse nome nesta turma? (cadastro exige nome unico) */
    public function nomeExisteNaTurma(int $turmaId, string $nome): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
               FROM turma_alunos ta
               JOIN users u ON u.id = ta.aluno_id
              WHERE ta.turma_id = :t
                AND LOWER(TRIM(u.name)) = LOWER(TRIM(:nome))
              LIMIT 1'
        );
        $stmt->execute(['t' => $turmaId, 'nome' => $nome]);

        return (bool) $stmt->fetchColumn();
    }

    /** Uma matricula desta turma (garante o vinculo antes de ativar/inativar). */
    public function find(int $matriculaId, int $turmaId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM turma_alunos WHERE id = :id AND turma_id = :t LIMIT 1'
        );
        $stmt->execute(['id' => $matriculaId, 't' => $turmaId]);

        return $stmt->fetch() ?: null;
    }

    public function definirSituacao(int $matriculaId, string $situacao): void
    {
        $situacao = $situacao === 'inativa' ? 'inativa' : 'ativa';
        $stmt = $this->db->prepare('UPDATE turma_alunos SET situacao = :s WHERE id = :id');
        $stmt->execute(['s' => $situacao, 'id' => $matriculaId]);
    }

    public function contarDaTurma(int $turmaId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM turma_alunos WHERE turma_id = :t AND situacao = :sit'
        );
        $stmt->execute(['t' => $turmaId, 'sit' => 'ativa']);

        return (int) $stmt->fetchColumn();
    }
}
