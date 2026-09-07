<?php

namespace App\Models;

use App\Core\Model;

/**
 * Apresentacao de aula: roteiro de slides + resumo do assunto, por tema.
 * Espelha PlanoAula (posse via modulo -> disciplina -> user_id).
 */
class Apresentacao extends Model
{
    public function byModulo(int $moduloId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM apresentacoes WHERE modulo_id = :m ORDER BY id DESC'
        );
        $stmt->execute(['m' => $moduloId]);
        return $stmt->fetchAll();
    }

    /** Busca garantindo o dono; traz dados do tema e da turma. */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, m.id AS modulo_id, m.titulo AS modulo_titulo,
                    m.objetivos AS modulo_objetivos, m.codigos_bncc AS modulo_bncc,
                    d.nome AS disciplina_nome, d.etapa AS disciplina_etapa, d.turma_id
               FROM apresentacoes a
               JOIN modulos m ON m.id = a.modulo_id
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE a.id = :id AND d.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO apresentacoes (modulo_id, titulo, foco, slides_json, resumo, origem, status)
             VALUES (:m, :titulo, :foco, :slides, :resumo, :origem, :status)'
        );
        $stmt->execute([
            'm' => $data['modulo_id'],
            'titulo' => $data['titulo'],
            'foco' => ($data['foco'] ?? '') !== '' ? $data['foco'] : null,
            'slides' => $data['slides_json'] ?? null,
            'resumo' => ($data['resumo'] ?? '') !== '' ? $data['resumo'] : null,
            'origem' => $data['origem'] ?? 'manual',
            'status' => $data['status'] ?? 'rascunho',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE apresentacoes
                SET titulo = :titulo, foco = :foco, slides_json = :slides,
                    resumo = :resumo, status = :status
              WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'titulo' => $data['titulo'],
            'foco' => ($data['foco'] ?? '') !== '' ? $data['foco'] : null,
            'slides' => $data['slides_json'] ?? null,
            'resumo' => ($data['resumo'] ?? '') !== '' ? $data['resumo'] : null,
            'status' => $data['status'],
        ]);
    }

    public function definirMaterial(int $id, ?int $materialId): void
    {
        $stmt = $this->db->prepare('UPDATE apresentacoes SET material_id = :mid WHERE id = :id');
        $stmt->execute(['mid' => $materialId, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM apresentacoes WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
               FROM apresentacoes a
               JOIN modulos m ON m.id = a.modulo_id
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE d.user_id = :u'
        );
        $stmt->execute(['u' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /** Slides decodificados de uma linha da tabela. */
    public static function slides(array $row): array
    {
        $lista = json_decode($row['slides_json'] ?? '[]', true);
        return is_array($lista) ? $lista : [];
    }
}
