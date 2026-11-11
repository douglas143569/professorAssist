<?php

namespace App\Models;

use App\Core\Model;

/**
 * Consultas de leitura da pagina inicial (dashboard do professor).
 *
 * Fica separado dos models de cada entidade porque aqui so se AGREGA:
 * nada e criado ou alterado.
 */
class Dashboard extends Model
{
    /** Acoes de auditoria que nao interessam ao professor na "atividade recente". */
    private const IGNORAR_PREFIXOS = ['conta.', 'billing.', 'payment.', 'subscription.', 'aluno.'];

    /**
     * Quantos itens o professor criou no mes corrente, por tipo.
     *
     * @return array<string,int> turmas, questoes, planos, atividades,
     *                           conteudos, apresentacoes, provas
     */
    public function criadosNoMes(int $userId): array
    {
        $inicio = date('Y-m-01 00:00:00');

        // Cada SQL so difere na tabela e no caminho ate o dono (user_id).
        $consultas = [
            'turmas' => 'SELECT COUNT(*) FROM turmas x WHERE x.user_id = :u AND x.created_at >= :i',
            'questoes' => 'SELECT COUNT(*) FROM questoes x JOIN disciplinas d ON d.id = x.disciplina_id
                            WHERE d.user_id = :u AND x.created_at >= :i',
            'planos' => 'SELECT COUNT(*) FROM planos_aula x JOIN modulos m ON m.id = x.modulo_id
                          JOIN disciplinas d ON d.id = m.disciplina_id WHERE d.user_id = :u AND x.created_at >= :i',
            'atividades' => 'SELECT COUNT(*) FROM atividades x JOIN modulos m ON m.id = x.modulo_id
                              JOIN disciplinas d ON d.id = m.disciplina_id WHERE d.user_id = :u AND x.created_at >= :i',
            'conteudos' => 'SELECT COUNT(*) FROM conteudos x JOIN modulos m ON m.id = x.modulo_id
                             JOIN disciplinas d ON d.id = m.disciplina_id WHERE d.user_id = :u AND x.created_at >= :i',
            'apresentacoes' => 'SELECT COUNT(*) FROM apresentacoes x JOIN modulos m ON m.id = x.modulo_id
                                 JOIN disciplinas d ON d.id = m.disciplina_id WHERE d.user_id = :u AND x.created_at >= :i',
            'provas' => 'SELECT COUNT(*) FROM provas x WHERE x.user_id = :u AND x.created_at >= :i',
        ];

        $resultado = [];
        foreach ($consultas as $chave => $sql) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['u' => $userId, 'i' => $inicio]);
            $resultado[$chave] = (int) $stmt->fetchColumn();
        }

        return $resultado;
    }

    /**
     * Turmas do professor com contagem de alunos ativos e de materias.
     * So dado agregado: nenhum nome de aluno sai daqui.
     */
    public function turmasResumo(int $userId, int $limite = 4): array
    {
        $limite = max(1, min($limite, 20));

        $stmt = $this->db->prepare(
            "SELECT t.id, t.nome, t.etapa,
                    (SELECT COUNT(*) FROM turma_alunos ta
                      WHERE ta.turma_id = t.id AND ta.situacao = 'ativa') AS n_alunos,
                    (SELECT COUNT(*) FROM disciplinas d WHERE d.turma_id = t.id) AS n_materias
               FROM turmas t
              WHERE t.user_id = :u
              ORDER BY t.created_at DESC, t.id DESC
              LIMIT {$limite}"
        );
        $stmt->execute(['u' => $userId]);

        return $stmt->fetchAll();
    }

    /**
     * Ultimas acoes do professor (log de auditoria), sem ruido de cobranca
     * ou de conta. Devolve a descricao pronta e a data.
     */
    public function atividadeRecente(int $userId, int $limite = 5): array
    {
        $limite = max(1, min($limite, 20));

        $stmt = $this->db->prepare(
            'SELECT action, description, created_at
               FROM activity_logs
              WHERE user_id = :u
              ORDER BY id DESC
              LIMIT 60'
        );
        $stmt->execute(['u' => $userId]);

        $itens = [];
        foreach ($stmt->fetchAll() as $linha) {
            foreach (self::IGNORAR_PREFIXOS as $prefixo) {
                if (str_starts_with($linha['action'], $prefixo)) {
                    continue 2;
                }
            }

            $texto = trim((string) $linha['description']);
            $itens[] = [
                'texto' => $texto !== '' ? $texto : self::rotulo($linha['action']),
                'quando' => $linha['created_at'],
                'tipo' => explode('.', $linha['action'])[0],
            ];

            if (count($itens) >= $limite) {
                break;
            }
        }

        return $itens;
    }

    /** Texto de reserva quando o log nao trouxe descricao. */
    private static function rotulo(string $action): string
    {
        return match ($action) {
            'plano.generated' => 'Plano de aula gerado',
            'plano.approved' => 'Plano de aula aprovado',
            'questao.generated' => 'Questões geradas',
            'questao.approved' => 'Questão aprovada',
            'atividade.generated' => 'Atividades geradas',
            'conteudo.approved' => 'Conteúdo aprovado',
            'prova.created' => 'Prova criada',
            'turma.created' => 'Turma criada',
            'escola.created' => 'Escola cadastrada',
            'materia.created' => 'Matéria criada',
            'modulo.created' => 'Tema de aula criado',
            'evento.created' => 'Evento criado no calendário',
            'apresentacao.generated' => 'Apresentação gerada',
            default => 'Ação registrada',
        };
    }
}
