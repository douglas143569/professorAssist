<?php

namespace App\Models;

use App\Core\Model;

/**
 * Fila de revisao: tudo que a IA rascunhou e ainda espera o professor.
 *
 * O produto inteiro funciona em dois tempos -- a IA rascunha, o professor
 * aprova. O segundo tempo estava invisivel: dava para acumular dezenas de
 * rascunhos sem nenhuma tela dizer isso, e o gerador de provas (que so usa
 * questao aprovada) parecia quebrado por falta de material liberado.
 *
 * As cinco consultas ficam reunidas aqui, num lugar so, em vez de espalhadas
 * como um metodo 'pendentes' em cada model.
 */
class Revisao extends Model
{
    /** Tipos revisaveis: rotulo, tabela e para onde a tela leva. */
    public const TIPOS = [
        'questao' => ['rotulo' => 'Questões', 'singular' => 'Questão', 'url' => '/questoes/'],
        'conteudo' => ['rotulo' => 'Conteúdos', 'singular' => 'Conteúdo', 'url' => '/conteudos/'],
        'plano' => ['rotulo' => 'Planos de aula', 'singular' => 'Plano de aula', 'url' => '/planos/'],
        'atividade' => ['rotulo' => 'Atividades sugeridas', 'singular' => 'Atividade', 'url' => '/atividades/'],
        'creche' => ['rotulo' => 'Atividades da creche', 'singular' => 'Atividade da creche', 'url' => '/creche/atividades/'],
    ];

    /**
     * Quantos rascunhos existem, por tipo, mais o total.
     *
     * @return array{total:int, por_tipo: array<string,int>}
     */
    public function contar(int $userId): array
    {
        $stmt = $this->db->prepare($this->sqlUniao('COUNT(*) AS n', agrupado: true));
        $this->executarComUsuario($stmt, $userId);

        $porTipo = array_fill_keys(array_keys(self::TIPOS), 0);
        $total = 0;

        foreach ($stmt->fetchAll() as $linha) {
            $porTipo[$linha['tipo']] = (int) $linha['n'];
            $total += (int) $linha['n'];
        }

        return ['total' => $total, 'por_tipo' => $porTipo];
    }

    /**
     * Os rascunhos em si, do mais novo para o mais antigo.
     *
     * @param string|null $tipo filtra por um tipo (chave de TIPOS)
     * @return array<int, array{tipo:string, id:int, titulo:string, contexto:?string, criado_em:string}>
     */
    public function pendentes(int $userId, ?string $tipo = null, int $limite = 300): array
    {
        $sql = $this->sqlUniao('id, titulo, contexto, criado_em')
            . ' ORDER BY criado_em DESC, id DESC LIMIT ' . max(1, min($limite, 500));

        $stmt = $this->db->prepare($sql);
        $this->executarComUsuario($stmt, $userId);

        $linhas = $stmt->fetchAll();

        if ($tipo !== null && isset(self::TIPOS[$tipo])) {
            $linhas = array_values(array_filter($linhas, fn($l) => $l['tipo'] === $tipo));
        }

        return $linhas;
    }

    /**
     * Aprova um item, conferindo que ele pertence ao professor.
     * Devolve false se o tipo for desconhecido ou o item nao for dele.
     */
    public function aprovar(string $tipo, int $id, int $userId): bool
    {
        $tabelas = [
            'questao' => 'questoes',
            'conteudo' => 'conteudos',
            'plano' => 'planos_aula',
            'atividade' => 'atividades',
            'creche' => 'creche_atividades',
        ];

        if (!isset($tabelas[$tipo]) || !$this->pertenceAoProfessor($tipo, $id, $userId)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$tabelas[$tipo]} SET status = 'aprovado' WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);

        return true;
    }

    /* ------------------------------------------------------------------ */

    /**
     * A dona da informacao e a disciplina (que tem user_id); a creche guarda
     * o user_id direto. Cada SELECT abaixo devolve as mesmas colunas para que
     * o UNION funcione.
     */
    private function sqlUniao(string $colunas, bool $agrupado = false): string
    {
        $partes = [
            "SELECT 'questao' AS tipo, q.id, LEFT(q.enunciado, 160) AS titulo,
                    CONCAT(d.nome, COALESCE(CONCAT(' · ', m.titulo), '')) AS contexto, q.created_at AS criado_em
               FROM questoes q
               JOIN disciplinas d ON d.id = q.disciplina_id
               LEFT JOIN modulos m ON m.id = q.modulo_id
              WHERE q.status = 'rascunho' AND d.user_id = :u1",

            "SELECT 'conteudo', c.id, c.titulo,
                    CONCAT(d.nome, ' · ', m.titulo), c.created_at
               FROM conteudos c
               JOIN modulos m ON m.id = c.modulo_id
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE c.status = 'rascunho' AND d.user_id = :u2",

            "SELECT 'plano', p.id, p.titulo,
                    CONCAT(d.nome, ' · ', m.titulo), p.created_at
               FROM planos_aula p
               JOIN modulos m ON m.id = p.modulo_id
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE p.status = 'rascunho' AND d.user_id = :u3",

            "SELECT 'atividade', a.id, a.titulo,
                    CONCAT(d.nome, ' · ', m.titulo), a.created_at
               FROM atividades a
               JOIN modulos m ON m.id = a.modulo_id
               JOIN disciplinas d ON d.id = m.disciplina_id
              WHERE a.status = 'rascunho' AND d.user_id = :u4",

            "SELECT 'creche', ca.id, ca.titulo,
                    CONCAT(ca.faixa_etaria, ' · ', ca.campo_experiencia), ca.created_at
               FROM creche_atividades ca
              WHERE ca.status = 'rascunho' AND ca.user_id = :u5",
        ];

        $uniao = '(' . implode(") UNION ALL (", $partes) . ')';

        if ($agrupado) {
            return "SELECT tipo, {$colunas} FROM ({$uniao}) AS fila GROUP BY tipo";
        }

        return "SELECT tipo, {$colunas} FROM ({$uniao}) AS fila";
    }

    private function executarComUsuario(\PDOStatement $stmt, int $userId): void
    {
        $stmt->execute([
            'u1' => $userId, 'u2' => $userId, 'u3' => $userId,
            'u4' => $userId, 'u5' => $userId,
        ]);
    }

    /** Refaz a checagem de dono antes de gravar (nunca confie no id do POST). */
    private function pertenceAoProfessor(string $tipo, int $id, int $userId): bool
    {
        $checagens = [
            'questao' => 'SELECT 1 FROM questoes q JOIN disciplinas d ON d.id = q.disciplina_id
                           WHERE q.id = :id AND d.user_id = :u',
            'conteudo' => 'SELECT 1 FROM conteudos c JOIN modulos m ON m.id = c.modulo_id
                            JOIN disciplinas d ON d.id = m.disciplina_id
                           WHERE c.id = :id AND d.user_id = :u',
            'plano' => 'SELECT 1 FROM planos_aula p JOIN modulos m ON m.id = p.modulo_id
                         JOIN disciplinas d ON d.id = m.disciplina_id
                        WHERE p.id = :id AND d.user_id = :u',
            'atividade' => 'SELECT 1 FROM atividades a JOIN modulos m ON m.id = a.modulo_id
                             JOIN disciplinas d ON d.id = m.disciplina_id
                            WHERE a.id = :id AND d.user_id = :u',
            'creche' => 'SELECT 1 FROM creche_atividades WHERE id = :id AND user_id = :u',
        ];

        $stmt = $this->db->prepare($checagens[$tipo]);
        $stmt->execute(['id' => $id, 'u' => $userId]);

        return $stmt->fetchColumn() !== false;
    }
}
