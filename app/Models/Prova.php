<?php

namespace App\Models;

use App\Core\Model;

class Prova extends Model
{
    /** Provas do professor, da mais recente para a mais antiga. */
    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, d.nome AS disciplina_nome, t.nome AS turma_nome,
                    (SELECT COUNT(*) FROM prova_questoes pq WHERE pq.prova_id = p.id) AS n_questoes,
                    (SELECT COUNT(*) FROM prova_versoes pv WHERE pv.prova_id = p.id) AS n_versoes
               FROM provas p
               JOIN disciplinas d ON d.id = p.disciplina_id
               LEFT JOIN turmas t ON t.id = d.turma_id
              WHERE p.user_id = :u
              ORDER BY p.id DESC'
        );
        $stmt->execute(['u' => $userId]);

        return $stmt->fetchAll();
    }

    public function byDisciplina(int $disciplinaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM prova_questoes pq WHERE pq.prova_id = p.id) AS n_questoes
               FROM provas p
              WHERE p.disciplina_id = :d
              ORDER BY p.id DESC'
        );
        $stmt->execute(['d' => $disciplinaId]);

        return $stmt->fetchAll();
    }

    /** Busca uma prova garantindo o dono. Traz materia e turma para o cabecalho. */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, d.nome AS disciplina_nome, d.etapa AS disciplina_etapa,
                    t.nome AS turma_nome, e.nome AS escola_nome
               FROM provas p
               JOIN disciplinas d ON d.id = p.disciplina_id
               LEFT JOIN turmas t ON t.id = d.turma_id
               LEFT JOIN escolas e ON e.id = t.escola_id
              WHERE p.id = :id AND p.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);

        $prova = $stmt->fetch();

        if ($prova === false) {
            return null;
        }

        $prova['config'] = !empty($prova['config'])
            ? (json_decode($prova['config'], true) ?: [])
            : [];

        return $prova;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO provas (disciplina_id, user_id, titulo, instrucoes, config)
             VALUES (:d, :u, :titulo, :instrucoes, :config)'
        );
        $stmt->execute([
            'd' => $data['disciplina_id'],
            'u' => $data['user_id'],
            'titulo' => $data['titulo'],
            'instrucoes' => ($data['instrucoes'] ?? '') !== '' ? $data['instrucoes'] : null,
            'config' => !empty($data['config'])
                ? json_encode($data['config'], JSON_UNESCAPED_UNICODE)
                : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE provas SET titulo = :titulo, instrucoes = :instrucoes WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'titulo' => $data['titulo'],
            'instrucoes' => ($data['instrucoes'] ?? '') !== '' ? $data['instrucoes'] : null,
        ]);
    }

    public function delete(int $id): void
    {
        // prova_questoes e prova_versoes caem por ON DELETE CASCADE.
        $stmt = $this->db->prepare('DELETE FROM provas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM provas WHERE user_id = :u');
        $stmt->execute(['u' => $userId]);

        return (int) $stmt->fetchColumn();
    }
}
