<?php

namespace App\Models;

use App\Core\Model;

/**
 * Material de apoio da turma, visivel na Area do Aluno.
 * Fatia minima: por ora so 'resumo' (texto vindo de uma apresentacao).
 */
class Material extends Model
{
    /**
     * Cria ou atualiza o material ligado a uma origem (ex: 'apresentacao:123').
     * UNIQUE em origem garante 1 material por apresentacao. Devolve o id.
     */
    public function publicarResumo(int $turmaId, int $userId, string $origem, string $titulo, string $corpo): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO materiais (turma_id, user_id, titulo, corpo, tipo, origem, publicado, publicado_em)
             VALUES (:t, :u, :titulo, :corpo, \'resumo\', :origem, 1, NOW())
             ON DUPLICATE KEY UPDATE
                turma_id = VALUES(turma_id),
                titulo = VALUES(titulo),
                corpo = VALUES(corpo),
                publicado = 1,
                publicado_em = NOW()'
        );
        $stmt->execute([
            't' => $turmaId,
            'u' => $userId,
            'titulo' => $titulo,
            'corpo' => $corpo,
            'origem' => $origem,
        ]);

        $id = (int) $this->db->lastInsertId();

        if ($id > 0) {
            return $id;
        }

        // ON DUPLICATE KEY sem insert novo: busca o id existente.
        $existente = $this->porOrigem($origem);
        return $existente ? (int) $existente['id'] : 0;
    }

    public function porOrigem(string $origem): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM materiais WHERE origem = :o LIMIT 1');
        $stmt->execute(['o' => $origem]);
        return $stmt->fetch() ?: null;
    }

    public function removerPorOrigem(string $origem): void
    {
        $stmt = $this->db->prepare('DELETE FROM materiais WHERE origem = :o');
        $stmt->execute(['o' => $origem]);
    }

    public function daTurma(int $turmaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM materiais WHERE turma_id = :t ORDER BY publicado_em DESC, id DESC'
        );
        $stmt->execute(['t' => $turmaId]);
        return $stmt->fetchAll();
    }

    /** Materiais publicados das turmas ativas do aluno. */
    public function paraAluno(int $alunoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT mt.*, t.nome AS turma_nome
               FROM turma_alunos ta
               JOIN turmas t ON t.id = ta.turma_id
               JOIN materiais mt ON mt.turma_id = t.id
              WHERE ta.aluno_id = :a AND ta.situacao = \'ativa\' AND mt.publicado = 1
              ORDER BY mt.publicado_em DESC, mt.id DESC'
        );
        $stmt->execute(['a' => $alunoId]);
        return $stmt->fetchAll();
    }
}
