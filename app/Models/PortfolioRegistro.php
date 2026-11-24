<?php

namespace App\Models;

use App\Core\Model;

/** Registros pedagogicos (linha do tempo) e midias (galeria de evidencias). Sempre por user_id. */
class PortfolioRegistro extends Model
{
    public const CATEGORIAS = [
        'Rotina', 'Brincadeira livre', 'Atividade dirigida', 'Projeto', 'Passeio / Evento',
        'Alimentação', 'Música e movimento', 'Roda de conversa', 'Conquista',
    ];

    /** Linha do tempo: do mais novo ao mais antigo, com foto destaque e contagem de midias. */
    public function timeline(int $criancaId, int $userId, array $f = []): array
    {
        $sql = "SELECT r.*, p.titulo AS projeto_titulo,
                       (SELECT m.id FROM portfolio_midias m WHERE m.registro_id = r.id AND m.tipo IN ('foto','producao') ORDER BY m.id LIMIT 1) AS capa_id,
                       (SELECT COUNT(*) FROM portfolio_midias m WHERE m.registro_id = r.id) AS n_midias
                  FROM portfolio_registros r
                  LEFT JOIN portfolio_projetos p ON p.id = r.projeto_id
                 WHERE r.crianca_id = :c AND r.user_id = :u";
        $params = ['c' => $criancaId, 'u' => $userId];

        if (!empty($f['mes']) && preg_match('/^\d{4}-\d{2}$/', $f['mes'])) {
            $sql .= " AND DATE_FORMAT(r.data_registro, '%Y-%m') = :mes";
            $params['mes'] = $f['mes'];
        }
        if (!empty($f['projeto'])) {
            $sql .= ' AND r.projeto_id = :proj';
            $params['proj'] = (int) $f['projeto'];
        }
        if (!empty($f['categoria'])) {
            $sql .= ' AND r.categoria = :cat';
            $params['cat'] = $f['categoria'];
        }

        $stmt = $this->db->prepare($sql . ' ORDER BY r.data_registro DESC, r.id DESC');
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, p.titulo AS projeto_titulo FROM portfolio_registros r
               LEFT JOIN portfolio_projetos p ON p.id = r.projeto_id
              WHERE r.id = :id AND r.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public function create(int $userId, int $criancaId, array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO portfolio_registros
                (user_id, crianca_id, projeto_id, data_registro, titulo, descricao, categoria, area, objetivos, habilidades)
             VALUES (:u, :c, :proj, :data, :titulo, :descr, :cat, :area, :obj, :hab)'
        );
        $stmt->execute($this->campos($d) + ['u' => $userId, 'c' => $criancaId]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE portfolio_registros
                SET projeto_id = :proj, data_registro = :data, titulo = :titulo, descricao = :descr,
                    categoria = :cat, area = :area, objetivos = :obj, habilidades = :hab
              WHERE id = :id AND user_id = :u'
        );
        $stmt->execute($this->campos($d) + ['id' => $id, 'u' => $userId]);
    }

    private function campos(array $d): array
    {
        return [
            'proj' => $d['projeto_id'] ?: null,
            'data' => $d['data_registro'],
            'titulo' => $d['titulo'],
            'descr' => $d['descricao'],
            'cat' => $d['categoria'],
            'area' => $d['area'] ?: null,
            'obj' => $d['objetivos'] !== '' ? $d['objetivos'] : null,
            'hab' => $d['habilidades'] !== '' ? $d['habilidades'] : null,
        ];
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM portfolio_registros WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);
    }

    /** Ultimos registros do professor (dashboard), com o nome da crianca. */
    public function recentes(int $userId, int $limite = 6): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.id, r.titulo, r.data_registro, r.created_at, c.id AS crianca_id, c.nome,
                    (SELECT m.id FROM portfolio_midias m WHERE m.registro_id = r.id AND m.tipo IN ('foto','producao') ORDER BY m.id LIMIT 1) AS capa_id
               FROM portfolio_registros r JOIN portfolio_criancas c ON c.id = r.crianca_id
              WHERE r.user_id = :u ORDER BY r.created_at DESC, r.id DESC LIMIT " . (int) $limite
        );
        $stmt->execute(['u' => $userId]);

        return $stmt->fetchAll();
    }

    /** Numeros gerais do professor: registros no total / no mes, midias, criancas ativas no mes. */
    public function totais(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(DATE_FORMAT(data_registro, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')), 0) AS no_mes,
                    COALESCE(SUM(data_registro >= (CURRENT_DATE - INTERVAL 7 DAY)), 0) AS na_semana
               FROM portfolio_registros WHERE user_id = :u"
        );
        $stmt->execute(['u' => $userId]);

        return $stmt->fetch();
    }

    // ---------------------------------------------------------------- midias

    public function addMidia(int $userId, int $criancaId, ?int $registroId, ?string $area, string $tipo, string $path, string $nome, ?string $legenda = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO portfolio_midias (user_id, crianca_id, registro_id, area, tipo, path, nome_original, legenda)
             VALUES (:u, :c, :r, :a, :t, :p, :n, :l)'
        );
        $stmt->execute(['u' => $userId, 'c' => $criancaId, 'r' => $registroId, 'a' => $area, 't' => $tipo, 'p' => $path, 'n' => $nome, 'l' => $legenda]);

        return (int) $this->db->lastInsertId();
    }

    public function findMidia(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM portfolio_midias WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public function deleteMidia(int $id, int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM portfolio_midias WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);
    }

    public function midiasDoRegistro(int $registroId, int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM portfolio_midias WHERE registro_id = :r AND user_id = :u ORDER BY id');
        $stmt->execute(['r' => $registroId, 'u' => $userId]);

        return $stmt->fetchAll();
    }

    public function midiasDaArea(int $criancaId, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM portfolio_midias WHERE crianca_id = :c AND user_id = :u AND area IS NOT NULL AND registro_id IS NULL ORDER BY id'
        );
        $stmt->execute(['c' => $criancaId, 'u' => $userId]);

        return $stmt->fetchAll();
    }

    /** Galeria: todas as midias da crianca com origem (registro/projeto/categoria/mes) e filtros. */
    public function galeria(int $criancaId, int $userId, array $f = []): array
    {
        $sql = "SELECT m.*, r.titulo AS registro_titulo, r.categoria, r.projeto_id, p.titulo AS projeto_titulo,
                       COALESCE(r.data_registro, DATE(m.created_at)) AS data_ref
                  FROM portfolio_midias m
                  LEFT JOIN portfolio_registros r ON r.id = m.registro_id
                  LEFT JOIN portfolio_projetos p ON p.id = r.projeto_id
                 WHERE m.crianca_id = :c AND m.user_id = :u";
        $params = ['c' => $criancaId, 'u' => $userId];

        if (!empty($f['tipo']) && in_array($f['tipo'], ['foto', 'video', 'producao', 'arquivo'], true)) {
            $sql .= ' AND m.tipo = :tipo';
            $params['tipo'] = $f['tipo'];
        }
        if (!empty($f['mes']) && preg_match('/^\d{4}-\d{2}$/', $f['mes'])) {
            $sql .= " AND DATE_FORMAT(COALESCE(r.data_registro, DATE(m.created_at)), '%Y-%m') = :mes";
            $params['mes'] = $f['mes'];
        }
        if (!empty($f['projeto'])) {
            $sql .= ' AND r.projeto_id = :proj';
            $params['proj'] = (int) $f['projeto'];
        }
        if (!empty($f['categoria'])) {
            $sql .= ' AND r.categoria = :cat';
            $params['cat'] = $f['categoria'];
        }

        $stmt = $this->db->prepare($sql . ' ORDER BY data_ref DESC, m.id DESC');
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** Meses (AAAA-MM) com algum registro/midia, para o filtro da galeria e da linha do tempo. */
    public function meses(int $criancaId, int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT DATE_FORMAT(data_registro, '%Y-%m') FROM portfolio_registros
              WHERE crianca_id = :c AND user_id = :u ORDER BY 1 DESC"
        );
        $stmt->execute(['c' => $criancaId, 'u' => $userId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /** Caminhos de todas as midias da crianca (para apagar do disco antes de excluir). */
    public function pathsDaCrianca(int $criancaId, int $userId): array
    {
        $stmt = $this->db->prepare('SELECT path FROM portfolio_midias WHERE crianca_id = :c AND user_id = :u');
        $stmt->execute(['c' => $criancaId, 'u' => $userId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function pathsDoRegistro(int $registroId, int $userId): array
    {
        $stmt = $this->db->prepare('SELECT path FROM portfolio_midias WHERE registro_id = :r AND user_id = :u');
        $stmt->execute(['r' => $registroId, 'u' => $userId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /** Dados do projeto: registros ligados, agrupados por crianca. */
    public function doProjeto(int $projetoId, int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.id, r.titulo, r.descricao, r.data_registro, c.id AS crianca_id, c.nome,
                    (SELECT m.id FROM portfolio_midias m WHERE m.registro_id = r.id AND m.tipo IN ('foto','producao') ORDER BY m.id LIMIT 1) AS capa_id
               FROM portfolio_registros r JOIN portfolio_criancas c ON c.id = r.crianca_id
              WHERE r.projeto_id = :p AND r.user_id = :u ORDER BY c.nome, r.data_registro"
        );
        $stmt->execute(['p' => $projetoId, 'u' => $userId]);

        return $stmt->fetchAll();
    }
}
