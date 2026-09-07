<?php

namespace App\Models;

use App\Core\Model;

/**
 * Entrega de um aluno para uma tarefa.
 *
 * "Pendente" nao tem registro: e a ausencia de linha para (tarefa, aluno).
 * Ao enviar cria 'entregue'; ao o professor dar nota vira 'corrigida'.
 */
class TarefaEntrega extends Model
{
    /**
     * Cria ou atualiza a entrega do aluno (UNIQUE tarefa+aluno).
     * Reenvio depois de corrigida NAO e permitido -- o controller barra antes.
     */
    public function enviar(int $tarefaId, int $alunoId, ?string $texto, ?array $arquivo): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tarefa_entregas
                (tarefa_id, aluno_id, resposta_texto, arquivo_path, arquivo_nome, status, entregue_em)
             VALUES (:t, :a, :texto, :path, :nome, \'entregue\', NOW())
             ON DUPLICATE KEY UPDATE
                resposta_texto = VALUES(resposta_texto),
                arquivo_path   = COALESCE(VALUES(arquivo_path), arquivo_path),
                arquivo_nome   = COALESCE(VALUES(arquivo_nome), arquivo_nome),
                status = \'entregue\',
                entregue_em = NOW()'
        );
        $stmt->execute([
            't' => $tarefaId,
            'a' => $alunoId,
            'texto' => ($texto ?? '') !== '' ? $texto : null,
            'path' => $arquivo['path'] ?? null,
            'nome' => $arquivo['nome'] ?? null,
        ]);
    }

    /** Entrega de um aluno para uma tarefa (ou null se ainda pendente). */
    public function doAluno(int $tarefaId, int $alunoId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tarefa_entregas WHERE tarefa_id = :t AND aluno_id = :a LIMIT 1'
        );
        $stmt->execute(['t' => $tarefaId, 'a' => $alunoId]);

        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tarefa_entregas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Todos os alunos ativos da turma da tarefa + a entrega de cada um (ou NULL).
     * Base da grade de correcao do professor.
     */
    public function daTarefa(int $tarefaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id AS aluno_id, u.name AS aluno_nome,
                    te.id AS entrega_id, te.resposta_texto, te.arquivo_path, te.arquivo_nome,
                    COALESCE(te.status, \'pendente\') AS status,
                    te.nota, te.feedback, te.entregue_em, te.corrigida_em
               FROM tarefas tf
               JOIN turma_alunos ta ON ta.turma_id = tf.turma_id AND ta.situacao = \'ativa\'
               JOIN users u ON u.id = ta.aluno_id
          LEFT JOIN tarefa_entregas te ON te.tarefa_id = tf.id AND te.aluno_id = u.id
              WHERE tf.id = :t
              ORDER BY u.name'
        );
        $stmt->execute(['t' => $tarefaId]);

        return $stmt->fetchAll();
    }

    public function corrigir(int $id, float $nota, ?string $feedback): void
    {
        $stmt = $this->db->prepare(
            'UPDATE tarefa_entregas
                SET nota = :nota, feedback = :fb, status = \'corrigida\', corrigida_em = NOW()
              WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'nota' => $nota,
            'fb' => ($feedback ?? '') !== '' ? $feedback : null,
        ]);
    }

    /** Notas ja lancadas para o aluno, com titulo da tarefa e turma. */
    public function notasDoAluno(int $alunoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT te.nota, te.feedback, te.corrigida_em,
                    tf.id AS tarefa_id, tf.titulo, t.nome AS turma_nome
               FROM tarefa_entregas te
               JOIN tarefas tf ON tf.id = te.tarefa_id
               JOIN turmas t ON t.id = tf.turma_id
              WHERE te.aluno_id = :a AND te.status = \'corrigida\'
              ORDER BY te.corrigida_em DESC'
        );
        $stmt->execute(['a' => $alunoId]);

        return $stmt->fetchAll();
    }

    /** Contadores do painel do aluno: entregues, corrigidas e media das notas. */
    public function resumoDoAluno(int $alunoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                SUM(status = \'entregue\')  AS entregues,
                SUM(status = \'corrigida\') AS corrigidas,
                AVG(CASE WHEN status = \'corrigida\' THEN nota END) AS media
               FROM tarefa_entregas
              WHERE aluno_id = :a'
        );
        $stmt->execute(['a' => $alunoId]);
        $row = $stmt->fetch() ?: [];

        return [
            'entregues' => (int) ($row['entregues'] ?? 0),
            'corrigidas' => (int) ($row['corrigidas'] ?? 0),
            'media' => $row['media'] !== null ? round((float) $row['media'], 1) : null,
        ];
    }
}
