<?php

namespace App\Models;

use App\Core\Model;

/**
 * Versoes embaralhadas de uma prova (A, B, C...).
 *
 * A semente fica gravada: e ela que garante que reimprimir a versao B daqui
 * a um mes produz exatamente a mesma prova -- e portanto o mesmo gabarito.
 */
class ProvaVersao extends Model
{
    public function byProva(int $provaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM prova_versoes WHERE prova_id = :p ORDER BY rotulo'
        );
        $stmt->execute(['p' => $provaId]);

        return $stmt->fetchAll();
    }

    public function find(int $provaId, string $rotulo): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM prova_versoes WHERE prova_id = :p AND rotulo = :r LIMIT 1'
        );
        $stmt->execute(['p' => $provaId, 'r' => $rotulo]);

        return $stmt->fetch() ?: null;
    }

    public function criar(int $provaId, string $rotulo, int $seed): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO prova_versoes (prova_id, rotulo, seed_embaralhamento)
             VALUES (:p, :r, :seed)'
        );
        $stmt->execute(['p' => $provaId, 'r' => $rotulo, 'seed' => $seed]);
    }

    public function excluirTodas(int $provaId): void
    {
        $stmt = $this->db->prepare('DELETE FROM prova_versoes WHERE prova_id = :p');
        $stmt->execute(['p' => $provaId]);
    }
}
