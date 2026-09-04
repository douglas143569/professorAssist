<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\Logger;

/**
 * Administracao de contas: quem entra no sistema e com que poder.
 *
 * Restrita a administradores -- todas as acoes chamam exigirAdmin().
 *
 * NAO existe exclusao de conta aqui, de proposito: escolas, materias, temas,
 * questoes e provas apontam para o professor com ON DELETE CASCADE, entao
 * apagar a conta apagaria junto todo o trabalho dela. Para tirar alguem do
 * sistema, DESATIVE: o login para na hora, a sessao aberta cai na proxima
 * pagina e o conteudo continua intacto para um eventual retorno.
 */
class ContaController extends AppController
{
    private const SENHA_MINIMA = 8;

    public function index(): void
    {
        $this->exigirAdmin();

        $this->view('contas.index', [
            'title' => 'Contas',
            'contas' => (new User())->all(),
            'eu' => $this->professor(),
        ]);
    }

    public function criar(): void
    {
        $this->exigirAdmin();

        $nome = trim($_POST['name'] ?? '');
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        $role = ($_POST['role'] ?? '') === User::ROLE_ADMIN ? User::ROLE_ADMIN : User::ROLE_PROFESSOR;

        if ($nome === '' || $email === '') {
            $this->voltar('Informe o nome e o e-mail.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->voltar('E-mail inválido.');
        }

        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            $this->voltar('A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.');
        }

        $users = new User();

        if ($users->findByEmail($email) !== null) {
            $this->voltar('Já existe uma conta com esse e-mail.');
        }

        $id = $users->create($nome, $email, $senha, $role, $_POST['celular'] ?? null);

        Logger::activity('conta.created', [
            'entity_type' => 'user',
            'entity_id' => $id,
            'description' => "Conta criada para {$email}",
            'properties' => ['role' => $role],
        ]);

        $this->voltar("Conta de {$nome} criada. Passe a senha para a pessoa e peça que troque depois.");
    }

    /** Liga/desliga o acesso de uma conta. */
    public function alternarAtivo(string $id): void
    {
        $this->exigirAdmin();

        $conta = $this->contaExistente($id);
        $ativar = (int) $conta['ativo'] !== 1;

        if (!$ativar) {
            $this->exigirQueNaoTranqueOSistema($conta, 'desativar');
        }

        (new User())->definirAtivo((int) $conta['id'], $ativar);

        Logger::activity($ativar ? 'conta.activated' : 'conta.deactivated', [
            'entity_type' => 'user',
            'entity_id' => (int) $conta['id'],
            'description' => ($ativar ? 'Conta reativada: ' : 'Conta desativada: ') . $conta['email'],
        ]);

        $this->voltar($ativar
            ? "{$conta['name']} voltou a ter acesso."
            : "{$conta['name']} não entra mais no sistema. O conteúdo dela foi preservado.");
    }

    /** Promove a administrador ou rebaixa a professor. */
    public function alternarPapel(string $id): void
    {
        $this->exigirAdmin();

        $conta = $this->contaExistente($id);
        $virarAdmin = $conta['role'] !== User::ROLE_ADMIN;

        if (!$virarAdmin) {
            $this->exigirQueNaoTranqueOSistema($conta, 'rebaixar');
        }

        (new User())->definirRole((int) $conta['id'], $virarAdmin ? User::ROLE_ADMIN : User::ROLE_PROFESSOR);

        Logger::activity('conta.role_changed', [
            'entity_type' => 'user',
            'entity_id' => (int) $conta['id'],
            'description' => "Papel de {$conta['email']} alterado",
            'properties' => ['role' => $virarAdmin ? User::ROLE_ADMIN : User::ROLE_PROFESSOR],
        ]);

        $this->voltar($virarAdmin
            ? "{$conta['name']} agora é administrador e pode gerenciar contas."
            : "{$conta['name']} voltou a ser professor.");
    }

    /** Define uma nova senha (quem esqueceu a sua). */
    public function trocarSenha(string $id): void
    {
        $this->exigirAdmin();

        $conta = $this->contaExistente($id);
        $senha = (string) ($_POST['senha'] ?? '');

        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            $this->voltar('A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.');
        }

        (new User())->atualizarSenha((int) $conta['id'], $senha);

        Logger::activity('conta.password_reset', [
            'entity_type' => 'user',
            'entity_id' => (int) $conta['id'],
            'description' => "Senha redefinida para {$conta['email']}",
        ]);

        $this->voltar("Senha de {$conta['name']} redefinida.");
    }

    /* ------------------------------------------------------------------ */

    private function contaExistente(string $id): array
    {
        $conta = (new User())->find((int) $id);

        if ($conta === null) {
            $this->notFound('Conta nao encontrada.');
            exit;
        }

        return $conta;
    }

    /**
     * Impede ficar trancado para fora do proprio sistema.
     *
     * A regra que faz o trabalho e a primeira: ninguem mexe na propria conta.
     * Com ela, e impossivel zerar os administradores -- quem executa a acao
     * ja e um admin ativo, e o alvo e outra pessoa, entao sempre sobra pelo
     * menos um.
     *
     * A segunda checagem e, hoje, inalcancavel por esse motivo. Ela fica como
     * rede de seguranca do invariante "nunca zero administradores ativos": se
     * um dia a regra da propria conta for afrouxada (por exemplo, para
     * permitir que um admin renuncie ao cargo), e ela que evita o sistema
     * ficar sem ninguem capaz de gerenciar contas.
     */
    private function exigirQueNaoTranqueOSistema(array $conta, string $acao): void
    {
        if ((int) $conta['id'] === (int) $this->professor()['id']) {
            $this->voltar("Você não pode {$acao} a sua própria conta — peça a outro administrador.");
        }

        if ($conta['role'] === User::ROLE_ADMIN && (new User())->countAdmins() <= 1) {
            $this->voltar("Esta é a única conta de administrador ativa. Promova outra antes de {$acao} esta.");
        }
    }

    private function voltar(string $mensagem): void
    {
        $this->flash($mensagem);
        $this->redirect('/admin/contas');
    }
}
