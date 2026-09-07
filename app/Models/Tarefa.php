<?php

namespace App\Models;

use App\Core\Model;

/**
 * Tarefa liberada pelo professor para uma turma.
 *
 * Nasce como rascunho (publicada = 0). O aluno so enxerga depois de publicada.
 */
class Tarefa extends Model
{
    public const TIPOS_ENTREGA = [
        'texto' => 'Resposta em texto',
        'arquivo' => 'Envio de arquivo',
        'ambos' => 'Texto e/ou arquivo',
    ];

    public function criar(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tarefas
                (turma_id, user_id, disciplina_id, titulo, descricao, tipo_entrega, entrega_ate)
             VALUES (:turma, :user, :disc, :titulo, :descricao, :tipo, :ate)'
        );
        $stmt->execute([
            'turma' => $d['turma_id'],
            'user' => $d['user_id'],
            'disc' => ($d['disciplina_id'] ?? null) ?: null,
            'titulo' => $d['titulo'],
            'descricao' => ($d['descricao'] ?? '') !== '' ? $d['descricao'] : null,
            'tipo' => isset(self::TIPOS_ENTREGA[$d['tipo_entrega'] ?? '']) ? $d['tipo_entrega'] : 'ambos',
            'ate' => ($d['entrega_ate'] ?? '') !== '' ? $d['entrega_ate'] : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE tarefas
                SET titulo = :titulo, descricao = :descricao,
                    tipo_entrega = :tipo, entrega_ate = :ate
              WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'titulo' => $d['titulo'],
            'descricao' => ($d['descricao'] ?? '') !== '' ? $d['descricao'] : null,
            'tipo' => isset(self::TIPOS_ENTREGA[$d['tipo_entrega'] ?? '']) ? $d['tipo_entrega'] : 'ambos',
            'ate' => ($d['entrega_ate'] ?? '') !== '' ? $d['entrega_ate'] : null,
        ]);
    }

    public function publicar(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE tarefas SET publicada = 1, publicada_em = NOW()
              WHERE id = :id AND publicada = 0'
        );
        $stmt->execute(['id' => $id]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM tarefas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** Tarefa garantindo que a turma pertence ao professor. Traz dados da turma. */
    public function findDoProfessor(int $id, int $profId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT tf.*, t.nome AS turma_nome, t.escola_id, e.nome AS escola_nome
               FROM tarefas tf
               JOIN turmas t ON t.id = tf.turma_id
          LEFT JOIN escolas e ON e.id = t.escola_id
              WHERE tf.id = :id AND t.user_id = :u
              LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'u' => $profId]);

        return $stmt->fetch() ?: null;
    }

    /** Tarefas de uma turma (para a tela da turma do professor). */
    public function daTurma(int $turmaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT tf.*,
                    (SELECT COUNT(*) FROM tarefa_entregas te WHERE te.tarefa_id = tf.id) AS n_entregas,
                    (SELECT COUNT(*) FROM tarefa_entregas te
                      WHERE te.tarefa_id = tf.id AND te.status = \'corrigida\') AS n_corrigidas
               FROM tarefas tf
              WHERE tf.turma_id = :t
              ORDER BY tf.publicada ASC, COALESCE(tf.entrega_ate, tf.created_at) DESC'
        );
        $stmt->execute(['t' => $turmaId]);

        return $stmt->fetchAll();
    }

    /**
     * Tarefas publicadas das turmas do aluno, com o status da entrega dele.
     * status calculado: 'pendente' quando ainda nao ha linha em tarefa_entregas.
     */
    public function publicadasParaAluno(int $alunoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT tf.id, tf.titulo, tf.descricao, tf.tipo_entrega, tf.entrega_ate,
                    tf.publicada_em, t.nome AS turma_nome,
                    COALESCE(te.status, \'pendente\') AS status,
                    te.nota, te.entregue_em
               FROM turma_alunos ta
               JOIN turmas t ON t.id = ta.turma_id
               JOIN tarefas tf ON tf.turma_id = t.id AND tf.publicada = 1
          LEFT JOIN tarefa_entregas te ON te.tarefa_id = tf.id AND te.aluno_id = ta.aluno_id
              WHERE ta.aluno_id = :a AND ta.situacao = \'ativa\'
              ORDER BY (tf.entrega_ate IS NULL), tf.entrega_ate ASC, tf.publicada_em DESC'
        );
        $stmt->execute(['a' => $alunoId]);

        return $stmt->fetchAll();
    }

    /** Uma tarefa publicada visivel para o aluno (turma dele). */
    public function findParaAluno(int $tarefaId, int $alunoId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT tf.*, t.nome AS turma_nome
               FROM turma_alunos ta
               JOIN turmas t ON t.id = ta.turma_id
               JOIN tarefas tf ON tf.turma_id = t.id AND tf.publicada = 1
              WHERE tf.id = :id AND ta.aluno_id = :a AND ta.situacao = \'ativa\'
              LIMIT 1'
        );
        $stmt->execute(['id' => $tarefaId, 'a' => $alunoId]);

        return $stmt->fetch() ?: null;
    }
}
