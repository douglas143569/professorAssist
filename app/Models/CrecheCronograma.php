<?php

namespace App\Models;

use App\Core\Model;

class CrecheCronograma extends Model
{
    /** Itens de uma semana (intervalo de datas), ordenados por dia. */
    public function byWeek(int $userId, string $inicio, string $fim): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM creche_cronograma
              WHERE user_id = :u AND data BETWEEN :ini AND :fim
              ORDER BY data, id'
        );
        $stmt->execute(['u' => $userId, 'ini' => $inicio, 'fim' => $fim]);
        return $stmt->fetchAll();
    }

    public function contarNaSemana(int $userId, string $inicio, string $fim): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM creche_cronograma
              WHERE user_id = :u AND data BETWEEN :ini AND :fim'
        );
        $stmt->execute(['u' => $userId, 'ini' => $inicio, 'fim' => $fim]);
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM creche_cronograma WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO creche_cronograma
                (user_id, faixa_etaria, campo_experiencia, data, titulo, descricao, materiais, duracao, origem)
             VALUES
                (:u, :faixa, :campo, :data, :titulo, :descricao, :materiais, :duracao, :origem)'
        );
        $stmt->execute([
            'u' => $data['user_id'],
            'faixa' => $data['faixa_etaria'],
            'campo' => ($data['campo_experiencia'] ?? '') !== '' ? $data['campo_experiencia'] : null,
            'data' => $data['data'],
            'titulo' => $data['titulo'],
            'descricao' => ($data['descricao'] ?? '') !== '' ? $data['descricao'] : null,
            'materiais' => ($data['materiais'] ?? '') !== '' ? $data['materiais'] : null,
            'duracao' => ($data['duracao'] ?? '') !== '' ? $data['duracao'] : null,
            'origem' => $data['origem'] ?? 'ia',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE creche_cronograma SET
                faixa_etaria = :faixa, campo_experiencia = :campo, data = :data,
                titulo = :titulo, descricao = :descricao, materiais = :materiais, duracao = :duracao
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'faixa' => $data['faixa_etaria'],
            'campo' => ($data['campo_experiencia'] ?? '') !== '' ? $data['campo_experiencia'] : null,
            'data' => $data['data'],
            'titulo' => $data['titulo'],
            'descricao' => ($data['descricao'] ?? '') !== '' ? $data['descricao'] : null,
            'materiais' => ($data['materiais'] ?? '') !== '' ? $data['materiais'] : null,
            'duracao' => ($data['duracao'] ?? '') !== '' ? $data['duracao'] : null,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM creche_cronograma WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** Limpa todos os itens de uma semana (usado pelo botao Limpar). */
    public function deleteWeek(int $userId, string $inicio, string $fim): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM creche_cronograma WHERE user_id = :u AND data BETWEEN :ini AND :fim'
        );
        $stmt->execute(['u' => $userId, 'ini' => $inicio, 'fim' => $fim]);
    }
}
