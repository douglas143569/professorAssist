<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * Direitos do titular (LGPD art. 18) para a conta de PROFESSOR:
 *  - exportar()  -> copia dos dados (portabilidade/acesso)
 *  - excluir()   -> eliminacao do conteudo + anonimizacao da conta
 *
 * Na exclusao, o que a lei manda reter (assinaturas/pagamentos por prazo fiscal
 * e registros de acesso por 6 meses - Marco Civil) FICA, ligado a uma conta
 * anonimizada: sem nome, e-mail ou celular, e sem poder entrar de novo.
 */
class ContaPrivacidade
{
    /** Tabelas que o professor "possui" diretamente (coluna user_id). */
    private const POR_USER_ID = [
        'escolas', 'turmas', 'disciplinas', 'eventos', 'avisos', 'tarefas', 'provas', 'materiais',
        'creche_atividades', 'creche_cronograma', 'creche_cronograma_mes', 'creche_pacotes',
        'creche_relatorios', 'subscriptions', 'payments', 'access_logs', 'activity_logs',
    ];

    /** Tabelas ligadas ao professor por turma/materia/tema. */
    private const POR_HIERARQUIA = [
        'modulos' => 'SELECT m.* FROM modulos m JOIN disciplinas d ON d.id = m.disciplina_id WHERE d.user_id = :u',
        'conteudos' => 'SELECT c.* FROM conteudos c JOIN modulos m ON m.id = c.modulo_id JOIN disciplinas d ON d.id = m.disciplina_id WHERE d.user_id = :u',
        'planos_aula' => 'SELECT p.* FROM planos_aula p JOIN modulos m ON m.id = p.modulo_id JOIN disciplinas d ON d.id = m.disciplina_id WHERE d.user_id = :u',
        'apresentacoes' => 'SELECT a.* FROM apresentacoes a JOIN modulos m ON m.id = a.modulo_id JOIN disciplinas d ON d.id = m.disciplina_id WHERE d.user_id = :u',
        'atividades' => 'SELECT a.* FROM atividades a JOIN modulos m ON m.id = a.modulo_id JOIN disciplinas d ON d.id = m.disciplina_id WHERE d.user_id = :u',
        'questoes' => 'SELECT q.* FROM questoes q JOIN disciplinas d ON d.id = q.disciplina_id WHERE d.user_id = :u',
    ];

    /** Fica retido por obrigacao legal (nao e apagado na exclusao). */
    private const RETIDAS = ['subscriptions', 'payments', 'access_logs', 'activity_logs', 'user_access_overrides', 'user_promotions'];

    /** Colunas que nunca saem na exportacao. */
    private const OCULTAR = ['password_hash'];

    public static function exportar(int $userId): array
    {
        $db = Database::getConnection();
        $dados = [];

        $stmt = $db->prepare('SELECT id, name, email, celular, role, ativo, last_login_at, aceite_privacidade_em, aceite_versao, created_at FROM users WHERE id = :u');
        $stmt->execute(['u' => $userId]);
        $dados['conta'] = $stmt->fetch() ?: [];

        foreach (self::POR_USER_ID as $tabela) {
            $dados[$tabela] = self::consultar($db, "SELECT * FROM {$tabela} WHERE user_id = :u", $userId);
        }
        foreach (self::POR_HIERARQUIA as $tabela => $sql) {
            $dados[$tabela] = self::consultar($db, $sql, $userId);
        }

        // Historico de IA: so os metadados (o texto gerado ja esta nos conteudos acima).
        $dados['ai_geracoes'] = self::consultar(
            $db,
            'SELECT id, tipo, modelo, tokens_in, tokens_out, status, created_at FROM ai_geracoes WHERE user_id = :u',
            $userId
        );

        // Alunos das turmas do professor: so o necessario (nome e turma, sem senha).
        $dados['alunos_das_turmas'] = self::consultar(
            $db,
            'SELECT u.id, u.name, t.id AS turma_id, ta.created_at AS matriculado_em
               FROM turma_alunos ta JOIN turmas t ON t.id = ta.turma_id JOIN users u ON u.id = ta.aluno_id
              WHERE t.user_id = :u',
            $userId
        );

        return [
            'exportado_em' => date('c'),
            'politica_versao' => Legal::VERSAO,
            'observacao' => 'Cópia dos seus dados pessoais e do conteúdo criado no Assistente do Professor (LGPD, art. 18).',
            'dados' => $dados,
        ];
    }

    /**
     * Elimina o conteudo do professor e anonimiza a conta. Retorna as quantidades apagadas.
     * @return array{linhas:int, alunos:int}
     */
    public static function excluir(int $userId): array
    {
        $db = Database::getConnection();

        // 1) Arquivos em disco (entregas de alunos e midias de apresentacoes) - antes de apagar as linhas.
        $tarefaIds = self::colunaDeIds($db, 'SELECT id FROM tarefas WHERE user_id = :u', $userId);
        $aprIds = self::colunaDeIds(
            $db,
            'SELECT a.id FROM apresentacoes a JOIN modulos m ON m.id = a.modulo_id JOIN disciplinas d ON d.id = m.disciplina_id WHERE d.user_id = :u',
            $userId
        );
        $alunoIds = self::colunaDeIds(
            $db,
            'SELECT DISTINCT ta.aluno_id FROM turma_alunos ta JOIN turmas t ON t.id = ta.turma_id WHERE t.user_id = :u',
            $userId
        );
        $base = dirname(__DIR__, 2) . '/storage/uploads';
        foreach ($tarefaIds as $id) {
            self::apagarPasta($base . '/entregas/' . (int) $id);
        }
        foreach ($aprIds as $id) {
            self::apagarPasta($base . '/apresentacoes/' . (int) $id);
        }

        $linhas = 0;
        $db->beginTransaction();
        try {
            // 2) Conteudo: apagar escolas/turmas/disciplinas leva junto (ON DELETE CASCADE) temas,
            //    conteudos, questoes, entregas, matriculas etc. O resto, tabela a tabela.
            foreach (['escolas', 'turmas', 'disciplinas', 'eventos', 'avisos', 'tarefas', 'provas', 'materiais',
                      'creche_atividades', 'creche_cronograma', 'creche_cronograma_mes', 'creche_pacotes',
                      'creche_relatorios', 'ai_geracoes'] as $tabela) {
                if (in_array($tabela, self::RETIDAS, true)) {
                    continue;
                }
                $stmt = $db->prepare("DELETE FROM {$tabela} WHERE user_id = :u");
                $stmt->execute(['u' => $userId]);
                $linhas += $stmt->rowCount();
            }

            // 3) Alunos que ficaram sem nenhuma turma (so existiam nas turmas deste professor).
            $apagados = 0;
            foreach ($alunoIds as $alunoId) {
                $stmt = $db->prepare(
                    "DELETE FROM users WHERE id = :a AND role = 'aluno'
                        AND NOT EXISTS (SELECT 1 FROM turma_alunos WHERE aluno_id = :a2)"
                );
                $stmt->execute(['a' => $alunoId, 'a2' => $alunoId]);
                $apagados += $stmt->rowCount();
            }

            // 4) Anonimiza a conta: some nome/e-mail/celular, bloqueia login, senha inutilizavel.
            $stmt = $db->prepare(
                "UPDATE users SET name = :nome, email = NULL, celular = NULL, ativo = 0,
                        password_hash = :hash, excluido_em = NOW()
                  WHERE id = :u"
            );
            $stmt->execute([
                'nome' => 'Conta excluída #' . $userId,
                'hash' => password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
                'u' => $userId,
            ]);

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return ['linhas' => $linhas, 'alunos' => $apagados];
    }

    private static function consultar(PDO $db, string $sql, int $userId): array
    {
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['u' => $userId]);
            $linhas = $stmt->fetchAll();
        } catch (Throwable $e) {
            Logger::exception($e);
            return [];
        }
        foreach ($linhas as &$l) {
            foreach (self::OCULTAR as $c) {
                unset($l[$c]);
            }
        }
        return $linhas;
    }

    /** @return int[] */
    private static function colunaDeIds(PDO $db, string $sql, int $userId): array
    {
        $stmt = $db->prepare($sql);
        $stmt->execute(['u' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private static function apagarPasta(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $p = $dir . '/' . $f;
            is_dir($p) ? self::apagarPasta($p) : @unlink($p);
        }
        @rmdir($dir);
    }
}
