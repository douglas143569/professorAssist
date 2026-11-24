<?php

namespace App\Models;

use App\Core\Model;

/** Projetos pedagogicos (ex.: "Descobrindo as Cores") aos quais os registros se ligam. */
class PortfolioProjeto extends Model
{
    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM portfolio_registros r WHERE r.projeto_id = p.id) AS n_registros,
                    (SELECT COUNT(DISTINCT r.crianca_id) FROM portfolio_registros r WHERE r.projeto_id = p.id) AS n_criancas
               FROM portfolio_projetos p WHERE p.user_id = :u ORDER BY COALESCE(p.data_inicio, p.created_at) DESC'
        );
        $stmt->execute(['u' => $userId]);

        return $stmt->fetchAll();
    }

    /** So id e titulo, para os selects de formulario. */
    public function opcoes(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT id, titulo FROM portfolio_projetos WHERE user_id = :u ORDER BY titulo');
        $stmt->execute(['u' => $userId]);

        return $stmt->fetchAll();
    }

    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM portfolio_projetos WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public function create(int $userId, array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO portfolio_projetos (user_id, titulo, objetivos, atividades, data_inicio, data_fim)
             VALUES (:u, :t, :o, :a, :i, :f)'
        );
        $stmt->execute($this->campos($d) + ['u' => $userId]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE portfolio_projetos SET titulo = :t, objetivos = :o, atividades = :a, data_inicio = :i, data_fim = :f
              WHERE id = :id AND user_id = :u'
        );
        $stmt->execute($this->campos($d) + ['id' => $id, 'u' => $userId]);
    }

    private function campos(array $d): array
    {
        return [
            't' => $d['titulo'],
            'o' => $d['objetivos'] !== '' ? $d['objetivos'] : null,
            'a' => $d['atividades'] !== '' ? $d['atividades'] : null,
            'i' => $d['data_inicio'] ?: null,
            'f' => $d['data_fim'] ?: null,
        ];
    }

    /** Os registros ligados ficam sem projeto (FK SET NULL); nada e apagado. */
    public function delete(int $id, int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM portfolio_projetos WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);
    }
}
