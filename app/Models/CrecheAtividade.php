<?php

namespace App\Models;

use App\Core\Model;

class CrecheAtividade extends Model
{
    /** Lista as atividades do professor, com filtro opcional por faixa. */
    public function byUser(int $userId, string $faixa = ''): array
    {
        $sql = 'SELECT * FROM creche_atividades WHERE user_id = :u';
        $params = ['u' => $userId];

        if ($faixa !== '') {
            $sql .= ' AND faixa_etaria = :faixa';
            $params['faixa'] = $faixa;
        }
        $sql .= ' ORDER BY id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM creche_atividades WHERE id = :id AND user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO creche_atividades
                (user_id, faixa_etaria, campo_experiencia, titulo, descricao, materiais, duracao, origem, status)
             VALUES
                (:u, :faixa, :campo, :titulo, :descricao, :materiais, :duracao, :origem, :status)'
        );
        $stmt->execute([
            'u' => $data['user_id'],
            'faixa' => $data['faixa_etaria'],
            'campo' => ($data['campo_experiencia'] ?? '') !== '' ? $data['campo_experiencia'] : null,
            'titulo' => $data['titulo'],
            'descricao' => ($data['descricao'] ?? '') !== '' ? $data['descricao'] : null,
            'materiais' => ($data['materiais'] ?? '') !== '' ? $data['materiais'] : null,
            'duracao' => ($data['duracao'] ?? '') !== '' ? $data['duracao'] : null,
            'origem' => $data['origem'] ?? 'manual',
            'status' => $data['status'] ?? 'rascunho',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE creche_atividades SET
                faixa_etaria = :faixa, campo_experiencia = :campo, titulo = :titulo,
                descricao = :descricao, materiais = :materiais, duracao = :duracao, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'faixa' => $data['faixa_etaria'],
            'campo' => ($data['campo_experiencia'] ?? '') !== '' ? $data['campo_experiencia'] : null,
            'titulo' => $data['titulo'],
            'descricao' => ($data['descricao'] ?? '') !== '' ? $data['descricao'] : null,
            'materiais' => ($data['materiais'] ?? '') !== '' ? $data['materiais'] : null,
            'duracao' => ($data['duracao'] ?? '') !== '' ? $data['duracao'] : null,
            'status' => $data['status'],
        ]);
    }

    public function aprovar(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE creche_atividades SET status = "aprovado" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM creche_atividades WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
