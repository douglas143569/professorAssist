<?php

namespace App\Models;

use App\Core\Model;

/**
 * Crianca do Portfolio Pedagogico + textos por area de desenvolvimento.
 * Dado pessoal de menor: toda consulta e filtrada por user_id (o professor dono).
 */
class PortfolioCrianca extends Model
{
    /** chave => [rotulo, icone, cor (rgb)] */
    public const AREAS = [
        'linguagem' => ['Linguagem Oral e Comunicação', '🗣️', '236,72,153'],
        'motora_fina' => ['Coordenação Motora Fina', '✏️', '245,158,11'],
        'motora_ampla' => ['Coordenação Motora Ampla', '🤸', '16,185,129'],
        'cognitivo' => ['Desenvolvimento Cognitivo', '🧩', '99,102,241'],
        'social' => ['Interação Social', '🤝', '14,165,233'],
        'autonomia' => ['Autonomia', '🌱', '132,204,22'],
        'artistica' => ['Expressão Artística', '🎨', '249,115,22'],
        'musical' => ['Musicalização', '🎵', '168,85,247'],
        'natureza' => ['Natureza e Sociedade', '🌎', '20,184,166'],
    ];

    /** Quantos dias sem registro para a crianca ser considerada "desatualizada". */
    public const DIAS_DESATUALIZADO = 14;

    /** Lista com os numeros usados no progresso, no dashboard e na coordenacao. */
    public function byUser(int $userId, string $busca = '', string $turma = ''): array
    {
        $sql = "SELECT c.*,
                  (SELECT COUNT(*) FROM portfolio_registros r WHERE r.crianca_id = c.id) AS n_registros,
                  (SELECT MAX(r.data_registro) FROM portfolio_registros r WHERE r.crianca_id = c.id) AS ultimo_registro,
                  (SELECT COUNT(*) FROM portfolio_midias m WHERE m.crianca_id = c.id) AS n_midias,
                  (SELECT COUNT(*) FROM portfolio_midias m WHERE m.crianca_id = c.id AND m.tipo = 'foto') AS n_fotos,
                  (SELECT COUNT(*) FROM portfolio_areas a WHERE a.crianca_id = c.id AND a.texto <> '') AS n_areas,
                  (SELECT COUNT(*) FROM portfolio_avaliacoes v WHERE v.crianca_id = c.id) AS n_aval,
                  (SELECT COUNT(*) FROM portfolio_avaliacoes v WHERE v.crianca_id = c.id
                      AND v.created_at >= (NOW() - INTERVAL 90 DAY)) AS n_aval_recente
                FROM portfolio_criancas c WHERE c.user_id = :u";
        $params = ['u' => $userId];

        if ($busca !== '') {
            $sql .= ' AND c.nome LIKE :b';
            $params['b'] = '%' . addcslashes($busca, '%_\\') . '%';
        }
        if ($turma !== '') {
            $sql .= ' AND c.turma = :t';
            $params['t'] = $turma;
        }

        $stmt = $this->db->prepare($sql . ' ORDER BY c.nome');
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM portfolio_criancas WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);

        return $stmt->fetch() ?: null;
    }

    /** Uma crianca com os mesmos numeros de byUser(). */
    public function findComResumo(int $id, int $userId): ?array
    {
        foreach ($this->byUser($userId) as $c) {
            if ((int) $c['id'] === $id) {
                return $c;
            }
        }

        return null;
    }

    public function turmas(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT DISTINCT turma FROM portfolio_criancas WHERE user_id = :u ORDER BY turma');
        $stmt->execute(['u' => $userId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function create(int $userId, array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO portfolio_criancas
                (user_id, nome, nascimento, turma, periodo, professora, data_matricula, familia, autoriza_imagem)
             VALUES (:u, :nome, :nasc, :turma, :periodo, :prof, :matr, :familia, :autoriza)'
        );
        $stmt->execute($this->perfil($userId, $d));

        return (int) $this->db->lastInsertId();
    }

    public function updatePerfil(int $id, int $userId, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE portfolio_criancas
                SET nome = :nome, nascimento = :nasc, turma = :turma, periodo = :periodo, professora = :prof,
                    data_matricula = :matr, familia = :familia, autoriza_imagem = :autoriza
              WHERE id = :id AND user_id = :u'
        );
        $stmt->execute($this->perfil($userId, $d) + ['id' => $id]);
    }

    private function perfil(int $userId, array $d): array
    {
        return [
            'u' => $userId,
            'nome' => $d['nome'],
            'nasc' => $d['nascimento'] ?: null,
            'turma' => $d['turma'],
            'periodo' => $d['periodo'],
            'prof' => $d['professora'],
            'matr' => $d['data_matricula'] ?: null,
            'familia' => $d['familia'] !== '' ? $d['familia'] : null,
            'autoriza' => !empty($d['autoriza_imagem']) ? 1 : 0,
        ];
    }

    public function updateApresentacao(int $id, int $userId, string $apresentacao, string $adaptacao, string $caracteristicas): void
    {
        $stmt = $this->db->prepare(
            'UPDATE portfolio_criancas SET apresentacao = :a, adaptacao = :d, caracteristicas = :c
              WHERE id = :id AND user_id = :u'
        );
        $stmt->execute(['a' => $apresentacao, 'd' => $adaptacao, 'c' => $caracteristicas, 'id' => $id, 'u' => $userId]);
    }

    public function updateMensagemFinal(int $id, int $userId, string $mensagem): void
    {
        $stmt = $this->db->prepare('UPDATE portfolio_criancas SET mensagem_final = :m WHERE id = :id AND user_id = :u');
        $stmt->execute(['m' => $mensagem, 'id' => $id, 'u' => $userId]);
    }

    public function setFoto(int $id, int $userId, ?string $path): void
    {
        $stmt = $this->db->prepare('UPDATE portfolio_criancas SET foto_path = :p WHERE id = :id AND user_id = :u');
        $stmt->execute(['p' => $path, 'id' => $id, 'u' => $userId]);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM portfolio_criancas WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);
    }

    /** @return array<string,string> area => texto */
    public function areas(int $criancaId, int $userId): array
    {
        $stmt = $this->db->prepare('SELECT area, texto FROM portfolio_areas WHERE crianca_id = :c AND user_id = :u');
        $stmt->execute(['c' => $criancaId, 'u' => $userId]);

        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function salvarArea(int $criancaId, int $userId, string $area, string $texto): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO portfolio_areas (user_id, crianca_id, area, texto) VALUES (:u, :c, :a, :t)
             ON DUPLICATE KEY UPDATE texto = VALUES(texto)'
        );
        $stmt->execute(['u' => $userId, 'c' => $criancaId, 'a' => $area, 't' => $texto]);
    }

    /**
     * Quanto do portfolio ja esta pronto (5 criterios, 20% cada).
     *
     * @param array $c linha de byUser()
     * @return array{pct:int, itens:array<string,bool>, completo:bool}
     */
    public static function progresso(array $c): array
    {
        $itens = [
            'Apresentação escrita' => trim((string) ($c['apresentacao'] ?? '')) !== '',
            'Ao menos 3 áreas descritas' => (int) $c['n_areas'] >= 3,
            'Ao menos 3 registros' => (int) $c['n_registros'] >= 3,
            'Ao menos 1 foto' => (int) $c['n_fotos'] >= 1,
            'Ao menos 1 avaliação' => (int) $c['n_aval'] >= 1,
        ];
        $ok = count(array_filter($itens));

        return ['pct' => (int) round($ok / count($itens) * 100), 'itens' => $itens, 'completo' => $ok === count($itens)];
    }

    /** A crianca esta sem registro novo ha muito tempo (ou nunca teve)? */
    public static function desatualizada(array $c): bool
    {
        if (empty($c['ultimo_registro'])) {
            return true;
        }

        return strtotime((string) $c['ultimo_registro']) < strtotime('-' . self::DIAS_DESATUALIZADO . ' days');
    }

    /** "3 anos e 2 meses" a partir da data de nascimento. */
    public static function idade(?string $nascimento): string
    {
        if (!$nascimento) {
            return '—';
        }
        $d = (new \DateTimeImmutable($nascimento))->diff(new \DateTimeImmutable('today'));
        $partes = [];
        if ($d->y > 0) {
            $partes[] = $d->y . ($d->y === 1 ? ' ano' : ' anos');
        }
        if ($d->m > 0 || !$partes) {
            $partes[] = $d->m . ($d->m === 1 ? ' mês' : ' meses');
        }

        return implode(' e ', $partes);
    }
}
