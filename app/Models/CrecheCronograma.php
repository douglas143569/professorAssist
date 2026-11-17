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
                (user_id, faixa_etaria, campo_experiencia, data, titulo, descricao, materiais, duracao, origem,
                 objetivos, habilidades, avaliacao, tipo_atividade, semana, mes_ref)
             VALUES
                (:u, :faixa, :campo, :data, :titulo, :descricao, :materiais, :duracao, :origem,
                :objetivos, :habilidades, :avaliacao, :tipo, :semana, :mes)'
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
            'objetivos' => ($data['objetivos'] ?? '') !== '' ? $data['objetivos'] : null,
            'habilidades' => ($data['habilidades'] ?? '') !== '' ? $data['habilidades'] : null,
            'avaliacao' => ($data['avaliacao'] ?? '') !== '' ? $data['avaliacao'] : null,
            'tipo' => ($data['tipo_atividade'] ?? '') !== '' ? $data['tipo_atividade'] : null,
            'semana' => $data['semana'] ?? null,
            'mes' => $data['mes_ref'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE creche_cronograma SET
                faixa_etaria = :faixa, campo_experiencia = :campo, data = :data,
                titulo = :titulo, descricao = :descricao, materiais = :materiais, duracao = :duracao,
                objetivos = :objetivos, habilidades = :habilidades, avaliacao = :avaliacao
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
            'objetivos' => ($data['objetivos'] ?? '') !== '' ? $data['objetivos'] : null,
            'habilidades' => ($data['habilidades'] ?? '') !== '' ? $data['habilidades'] : null,
            'avaliacao' => ($data['avaliacao'] ?? '') !== '' ? $data['avaliacao'] : null,
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

    /** Itens de um mes (AAAA-MM), por data. */
    public function byMonth(int $userId, string $mesRef): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM creche_cronograma
              WHERE user_id = :u AND data BETWEEN :ini AND :fim
              ORDER BY data, id'
        );
        $stmt->execute([
            'u' => $userId,
            'ini' => $mesRef . '-01',
            'fim' => date('Y-m-t', strtotime($mesRef . '-01')),
        ]);
        return $stmt->fetchAll();
    }

    /** Remove o mes inteiro (itens + resumo) antes de gerar de novo. */
    public function deleteMonth(int $userId, string $mesRef): void
    {
        $this->deleteWeek($userId, $mesRef . '-01', date('Y-m-t', strtotime($mesRef . '-01')));
        $stmt = $this->db->prepare('DELETE FROM creche_cronograma_mes WHERE user_id = :u AND mes_ref = :m');
        $stmt->execute(['u' => $userId, 'm' => $mesRef]);
    }

    public function resumoDoMes(int $userId, string $mesRef): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM creche_cronograma_mes WHERE user_id = :u AND mes_ref = :m');
        $stmt->execute(['u' => $userId, 'm' => $mesRef]);
        return $stmt->fetch() ?: null;
    }

    public function salvarResumoDoMes(array $d): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO creche_cronograma_mes
                (user_id, mes_ref, faixa_etaria, num_criancas, periodo, tema,
                 resumo_objetivos, competencias, adaptacoes, observacoes)
             VALUES (:u, :m, :faixa, :n, :periodo, :tema, :obj, :comp, :adap, :obs)
             ON DUPLICATE KEY UPDATE faixa_etaria = VALUES(faixa_etaria), num_criancas = VALUES(num_criancas),
                periodo = VALUES(periodo), tema = VALUES(tema), resumo_objetivos = VALUES(resumo_objetivos),
                competencias = VALUES(competencias), adaptacoes = VALUES(adaptacoes), observacoes = VALUES(observacoes)'
        );
        $stmt->execute([
            'u' => $d['user_id'], 'm' => $d['mes_ref'], 'faixa' => $d['faixa_etaria'],
            'n' => $d['num_criancas'] ?: null, 'periodo' => $d['periodo'],
            'tema' => $d['tema'] !== '' ? $d['tema'] : null,
            'obj' => $d['resumo_objetivos'] ?: null, 'comp' => $d['competencias'] ?: null,
            'adap' => $d['adaptacoes'] ?: null, 'obs' => $d['observacoes'] ?: null,
        ]);
    }
}
