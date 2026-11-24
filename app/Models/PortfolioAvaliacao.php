<?php

namespace App\Models;

use App\Core\Model;

/** Avaliacao descritiva por bimestre / trimestre / semestre / ano. */
class PortfolioAvaliacao extends Model
{
    /** periodicidade => [rotulo, opcoes de periodo] */
    public const PERIODICIDADES = [
        'bimestre' => ['Bimestral', ['1º bimestre', '2º bimestre', '3º bimestre', '4º bimestre']],
        'trimestre' => ['Trimestral', ['1º trimestre', '2º trimestre', '3º trimestre']],
        'semestre' => ['Semestral', ['1º semestre', '2º semestre']],
        'ano' => ['Anual', ['Ano letivo']],
    ];

    /** campo => rotulo */
    public const SECOES = [
        'socioemocional' => 'Aspectos socioemocionais',
        'linguagem' => 'Linguagem',
        'movimento' => 'Movimento',
        'interacao' => 'Interação',
        'participacao' => 'Participação',
        'autonomia' => 'Autonomia',
        'consideracoes' => 'Considerações gerais',
    ];

    public function byCrianca(int $criancaId, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM portfolio_avaliacoes WHERE crianca_id = :c AND user_id = :u ORDER BY ano DESC, id DESC'
        );
        $stmt->execute(['c' => $criancaId, 'u' => $userId]);

        return $stmt->fetchAll();
    }

    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, c.nome FROM portfolio_avaliacoes a JOIN portfolio_criancas c ON c.id = a.crianca_id
              WHERE a.id = :id AND a.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public function create(int $userId, int $criancaId, string $periodicidade, string $periodo, int $ano): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO portfolio_avaliacoes (user_id, crianca_id, periodicidade, periodo, ano) VALUES (:u, :c, :pd, :p, :a)'
        );
        $stmt->execute(['u' => $userId, 'c' => $criancaId, 'pd' => $periodicidade, 'p' => $periodo, 'a' => $ano]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, array $d): void
    {
        $sets = [];
        $params = ['id' => $id, 'u' => $userId];
        foreach (array_keys(self::SECOES) as $campo) {
            $sets[] = "{$campo} = :{$campo}";
            $texto = trim((string) ($d[$campo] ?? ''));
            $params[$campo] = $texto !== '' ? $texto : null;
        }
        $stmt = $this->db->prepare('UPDATE portfolio_avaliacoes SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :u');
        $stmt->execute($params);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM portfolio_avaliacoes WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);
    }
}
