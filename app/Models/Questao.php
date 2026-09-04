<?php

namespace App\Models;

use App\Core\Model;

class Questao extends Model
{
    public function byModulo(int $moduloId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM questoes WHERE modulo_id = :m ORDER BY id DESC'
        );
        $stmt->execute(['m' => $moduloId]);
        return $stmt->fetchAll();
    }

    /**
     * Banco de questoes de uma disciplina com filtros opcionais (RF-13):
     * dificuldade, status, tipo, habilidade_bncc (LIKE).
     */
    public function byDisciplinaFiltrado(int $disciplinaId, array $filtros = []): array
    {
        $sql = 'SELECT q.*, m.titulo AS modulo_titulo
                  FROM questoes q
                  LEFT JOIN modulos m ON m.id = q.modulo_id
                 WHERE q.disciplina_id = :d';
        $params = ['d' => $disciplinaId];

        foreach (['dificuldade', 'status', 'tipo'] as $campo) {
            if (!empty($filtros[$campo])) {
                $sql .= " AND q.{$campo} = :{$campo}";
                $params[$campo] = $filtros[$campo];
            }
        }
        if (!empty($filtros['bncc'])) {
            $sql .= ' AND q.habilidade_bncc LIKE :bncc';
            $params['bncc'] = '%' . $filtros['bncc'] . '%';
        }

        $sql .= ' ORDER BY q.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Questoes APROVADAS de uma materia, para montar prova (RF-15).
     *
     * So entram aprovadas: nada gerado pela IA vai para a mao do aluno sem o
     * professor ter revisado.
     *
     * @param array $filtros modulo_id (int), tipo (string), sem_dissertativa (bool)
     * @param int|null $foraDaProva ignora as questoes que ja estao nesta prova
     */
    public function aprovadasParaProva(int $disciplinaId, array $filtros = [], ?int $foraDaProva = null): array
    {
        $sql = "SELECT q.*, m.titulo AS modulo_titulo
                  FROM questoes q
                  LEFT JOIN modulos m ON m.id = q.modulo_id
                 WHERE q.disciplina_id = :d AND q.status = 'aprovado'";
        $params = ['d' => $disciplinaId];

        if (!empty($filtros['modulo_id'])) {
            $sql .= ' AND q.modulo_id = :modulo_id';
            $params['modulo_id'] = (int) $filtros['modulo_id'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= ' AND q.tipo = :tipo';
            $params['tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['sem_dissertativa'])) {
            $sql .= " AND q.tipo <> 'dissertativa'";
        }

        if ($foraDaProva !== null) {
            $sql .= ' AND q.id NOT IN (SELECT questao_id FROM prova_questoes WHERE prova_id = :prova)';
            $params['prova'] = $foraDaProva;
        }

        $sql .= ' ORDER BY q.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Quantas questoes APROVADAS cada materia do professor tem, por
     * dificuldade. Alimenta a tela de montagem de prova, para o professor
     * ver o que da para sortear ANTES de tentar montar.
     *
     * @return array<int, array{facil:int, media:int, dificil:int, total:int}>
     */
    public function aprovadasPorDificuldadeDoUsuario(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT q.disciplina_id, q.dificuldade, COUNT(*) AS total
               FROM questoes q
               JOIN disciplinas d ON d.id = q.disciplina_id
              WHERE d.user_id = :u AND q.status = 'aprovado'
              GROUP BY q.disciplina_id, q.dificuldade"
        );
        $stmt->execute(['u' => $userId]);

        $porMateria = [];
        foreach ($stmt->fetchAll() as $linha) {
            $id = (int) $linha['disciplina_id'];
            $porMateria[$id] ??= ['facil' => 0, 'media' => 0, 'dificil' => 0, 'total' => 0];
            $porMateria[$id][$linha['dificuldade']] = (int) $linha['total'];
            $porMateria[$id]['total'] += (int) $linha['total'];
        }

        return $porMateria;
    }

    /**
     * Conta as questoes do professor, opcionalmente por status.
     * @param string|null $status 'rascunho' | 'aprovado' | null (todas)
     */
    public function countByUser(int $userId, ?string $status = null): int
    {
        $sql = 'SELECT COUNT(*)
                  FROM questoes q
                  JOIN disciplinas d ON d.id = q.disciplina_id
                 WHERE d.user_id = :u';
        $params = ['u' => $userId];

        if ($status !== null) {
            $sql .= ' AND q.status = :status';
            $params['status'] = $status;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Busca uma questao garantindo o dono (via disciplina). */
    public function find(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT q.*, m.titulo AS modulo_titulo, d.nome AS disciplina_nome
               FROM questoes q
               LEFT JOIN modulos m ON m.id = q.modulo_id
               JOIN disciplinas d ON d.id = q.disciplina_id
              WHERE q.id = :id AND d.user_id = :u'
        );
        $stmt->execute(['id' => $id, 'u' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function alternativas(int $questaoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM questao_alternativas WHERE questao_id = :q ORDER BY ordem, id'
        );
        $stmt->execute(['q' => $questaoId]);
        return $stmt->fetchAll();
    }

    /**
     * Cria a questao e suas alternativas numa transacao.
     * @param array $alternativas lista de ['texto'=>string, 'correta'=>bool]
     */
    public function create(array $data, array $alternativas = []): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO questoes
                    (disciplina_id, modulo_id, tipo, enunciado, dificuldade,
                     habilidade_bncc, tags, origem, status)
                 VALUES
                    (:disciplina_id, :modulo_id, :tipo, :enunciado, :dificuldade,
                     :bncc, :tags, :origem, :status)'
            );
            $stmt->execute([
                'disciplina_id' => $data['disciplina_id'],
                'modulo_id' => $data['modulo_id'] ?? null,
                'tipo' => $data['tipo'],
                'enunciado' => $data['enunciado'],
                'dificuldade' => $data['dificuldade'] ?? 'media',
                'bncc' => ($data['habilidade_bncc'] ?? '') !== '' ? $data['habilidade_bncc'] : null,
                'tags' => ($data['tags'] ?? '') !== '' ? $data['tags'] : null,
                'origem' => $data['origem'] ?? 'manual',
                'status' => $data['status'] ?? 'rascunho',
            ]);
            $id = (int) $this->db->lastInsertId();

            $this->inserirAlternativas($id, $alternativas);

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Atualiza os campos da questao (nao mexe nas alternativas). */
    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE questoes SET
                tipo = :tipo, enunciado = :enunciado, dificuldade = :dificuldade,
                habilidade_bncc = :bncc, tags = :tags, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'tipo' => $data['tipo'],
            'enunciado' => $data['enunciado'],
            'dificuldade' => $data['dificuldade'],
            'bncc' => ($data['habilidade_bncc'] ?? '') !== '' ? $data['habilidade_bncc'] : null,
            'tags' => ($data['tags'] ?? '') !== '' ? $data['tags'] : null,
            'status' => $data['status'],
        ]);
    }

    /** Troca todas as alternativas da questao (usado na edicao). */
    public function substituirAlternativas(int $questaoId, array $alternativas): void
    {
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare('DELETE FROM questao_alternativas WHERE questao_id = :q');
            $del->execute(['q' => $questaoId]);

            $this->inserirAlternativas($questaoId, $alternativas);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function aprovar(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE questoes SET status = "aprovado" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        // As alternativas caem por ON DELETE CASCADE.
        $stmt = $this->db->prepare('DELETE FROM questoes WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function inserirAlternativas(int $questaoId, array $alternativas): void
    {
        if (empty($alternativas)) {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO questao_alternativas (questao_id, texto, correta, ordem)
             VALUES (:q, :texto, :correta, :ordem)'
        );

        $ordem = 0;
        foreach ($alternativas as $alt) {
            if (($alt['texto'] ?? '') === '') {
                continue;
            }
            $stmt->execute([
                'q' => $questaoId,
                'texto' => $alt['texto'],
                'correta' => !empty($alt['correta']) ? 1 : 0,
                'ordem' => $ordem++,
            ]);
        }
    }
}
