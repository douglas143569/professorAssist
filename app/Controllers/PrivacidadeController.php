<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\Auth;
use App\Services\ContaPrivacidade;
use App\Services\Legal;
use App\Services\Logger;

/**
 * "Meus dados": direitos do titular para o professor (LGPD art. 18).
 * Fica acessivel mesmo com a assinatura vencida (isencao no AppController).
 */
class PrivacidadeController extends AppController
{
    public function index(): void
    {
        $this->view('privacidade.meus_dados', [
            'title' => 'Meus dados e privacidade',
            'conta' => $this->professor() + (new User())->aceite((int) $this->professor()['id']),
            'versao' => Legal::VERSAO,
            'contato' => Legal::contatoEmail(),
        ]);
    }

    /** Baixa uma copia dos dados em JSON (portabilidade/acesso). */
    public function exportar(): void
    {
        $prof = $this->professor();
        $dados = ContaPrivacidade::exportar((int) $prof['id']);

        Logger::activity('lgpd.exportacao', [
            'entity_type' => 'user',
            'entity_id' => (int) $prof['id'],
            'description' => 'Titular exportou os proprios dados',
        ]);

        $json = json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="meus-dados-' . date('Y-m-d') . '.json"');
        header('Cache-Control: no-store');
        header('Content-Length: ' . strlen((string) $json));
        echo $json;
        exit;
    }

    /** Exclui o conteudo e anonimiza a conta. Exige senha + palavra de confirmacao. */
    public function excluir(): void
    {
        $prof = $this->professor();
        $id = (int) $prof['id'];

        if (($prof['role'] ?? '') === 'admin') {
            $this->flash('Contas de administrador não podem ser excluídas por aqui.');
            $this->redirect('/meus-dados');
            return;
        }

        if (mb_strtoupper(trim((string) ($_POST['confirmacao'] ?? ''))) !== 'EXCLUIR') {
            $this->flash('Digite EXCLUIR para confirmar.');
            $this->redirect('/meus-dados');
            return;
        }

        $com = (new User())->findByEmailComHash((string) $prof['email']);
        if (!$com || !password_verify((string) ($_POST['senha'] ?? ''), $com['password_hash'])) {
            Logger::warning('Exclusao de conta: senha incorreta', ['user_id' => $id]);
            $this->flash('Senha incorreta. Nada foi excluído.');
            $this->redirect('/meus-dados');
            return;
        }

        $res = ContaPrivacidade::excluir($id);

        Logger::activity('lgpd.conta_excluida', [
            'user_id' => $id,
            'entity_type' => 'user',
            'entity_id' => $id,
            'description' => "Titular excluiu a conta ({$res['linhas']} registros, {$res['alunos']} alunos sem turma)",
        ]);

        Auth::logout();
        Auth::iniciarSessao();
        $_SESSION['flash'] = 'Sua conta e o seu conteúdo foram excluídos. Obrigado por ter usado o sistema.';
        $this->redirect('/login');
    }
}
