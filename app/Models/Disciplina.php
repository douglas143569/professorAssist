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
            'SELECT d.*, t.nome AS turma_nome, t.escola_id, e.nome AS escola_nome
               FROM disciplinas d
               LEFT JOIN turmas t ON t.id = d.turma_id
               LEFT JOIN escolas e ON e.id = t.escola_id
              WHERE d.id = :id AND d.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    /** Totais reais da matéria e da turma associada. */
    public function resumo(int $disciplinaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                (SELECT COUNT(*) FROM turma_alunos ta WHERE ta.turma_id = d.turma_id AND ta.situacao = \'ativa\') AS n_alunos,
                (SELECT COUNT(*) FROM tarefas tf WHERE tf.turma_id = d.turma_id) AS n_tarefas,
                (SELECT COUNT(*) FROM avisos av WHERE av.turma_id = d.turma_id) AS n_avisos,
                (SELECT COUNT(*) FROM modulos m WHERE m.disciplina_id = d.id) AS n_temas,
                (SELECT COUNT(*) FROM conteudos c JOIN modulos m ON m.id = c.modulo_id WHERE m.disciplina_id = d.id) AS n_conteudos,
                (SELECT COUNT(*) FROM questoes q WHERE q.disciplina_id = d.id) AS n_questoes,
                (SELECT COUNT(*) FROM atividades a JOIN modulos m ON m.id = a.modulo_id WHERE m.disciplina_id = d.id) AS n_atividades,
                (SELECT COUNT(*) FROM planos_aula p JOIN modulos m ON m.id = p.modulo_id WHERE m.disciplina_id = d.id) AS n_planos,
                (SELECT COUNT(*) FROM apresentacoes ap JOIN modulos m ON m.id = ap.modulo_id WHERE m.disciplina_id = d.id) AS n_apresentacoes
             FROM disciplinas d WHERE d.id = :id'
        );
        $stmt->execute(['id' => $disciplinaId]);
        $resumo = $stmt->fetch() ?: [];
        foreach (['n_alunos', 'n_tarefas', 'n_avisos', 'n_temas', 'n_conteudos', 'n_questoes', 'n_atividades', 'n_planos', 'n_apresentacoes'] as $campo) {
            $resumo[$campo] = (int) ($resumo[$campo] ?? 0);
        }
        return $resumo;
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
