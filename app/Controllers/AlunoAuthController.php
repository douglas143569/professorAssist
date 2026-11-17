<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Escola;
use App\Models\Matricula;
use App\Models\Turma;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Logger;

/**
 * Login e autocadastro do ALUNO.
 *
 * Estende Controller (nao AppController): estas telas sao para quem ainda nao
 * esta logado. O aluno escolhe escola + turma em listas prontas e entra com
 * nome + senha (sem e-mail).
 */
class AlunoAuthController extends Controller
{
    private const SENHA_MINIMA = 8;

    public function loginForm(): void
    {
        Auth::iniciarSessao();

        if (Auth::ehAluno()) {
            $this->redirect('/aluno');
        }

        $erro = $_SESSION['aluno_login_erro'] ?? null;
        $dados = $_SESSION['aluno_login_dados'] ?? [];
        unset($_SESSION['aluno_login_erro'], $_SESSION['aluno_login_dados']);

        $this->viewSemLayout('aluno.login', [
            'erro' => $erro,
            'dados' => $dados,
            'turmas' => (new Turma())->todasComEscola(),
        ]);
    }

    public function login(): void
    {
        Auth::iniciarSessao();

        $turmaId = (int) ($_POST['turma_id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $dados = ['turma_id' => $turmaId, 'nome' => $nome];

        if (!Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            $this->voltarLogin('Sua sessão expirou. Tente entrar de novo.', $dados);
        }

        $senha = (string) ($_POST['senha'] ?? '');

        if ($turmaId <= 0 || $nome === '' || $senha === '') {
            $this->voltarLogin('Escolha a turma e preencha nome e senha.', $dados);
        }

        $resultado = Auth::tentarLoginAluno($turmaId, $nome, $senha);

        if (!$resultado['ok']) {
            $this->voltarLogin($resultado['erro'], $dados);
        }

        unset($_SESSION['destino']);
        $this->redirect('/aluno');
    }

    public function cadastroForm(): void
    {
        Auth::iniciarSessao();

        if (Auth::ehAluno()) {
            $this->redirect('/aluno');
        }

        $erro = $_SESSION['aluno_cadastro_erro'] ?? null;
        $dados = $_SESSION['aluno_cadastro_dados'] ?? [];
        unset($_SESSION['aluno_cadastro_erro'], $_SESSION['aluno_cadastro_dados']);

        $this->viewSemLayout('aluno.cadastro', [
            'erro' => $erro,
            'dados' => $dados,
            'turmas' => (new Turma())->todasComEscola(),
        ]);
    }

    public function cadastrar(): void
    {
        Auth::iniciarSessao();

        $turmaId = (int) ($_POST['turma_id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $dados = ['turma_id' => $turmaId, 'nome' => $nome];

        if (!Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            $this->erroCadastro('A página ficou aberta tempo demais. Tente enviar de novo.', $dados);
        }

        $senha = (string) ($_POST['senha'] ?? '');
        $confirmacao = (string) ($_POST['senha_confirmacao'] ?? '');

        if ($nome === '' || $turmaId <= 0) {
            $this->erroCadastro('Preencha seu nome e escolha a turma.', $dados);
        }

        if (mb_strlen($nome) < 3) {
            $this->erroCadastro('Escreva seu nome completo.', $dados);
        }

        $turma = (new Turma())->findPublica($turmaId);

        if ($turma === null) {
            $this->erroCadastro('Turma não encontrada. Escolha uma da lista.', $dados);
        }

        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            $this->erroCadastro('A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.', $dados);
        }

        if ($senha !== $confirmacao) {
            $this->erroCadastro('As duas senhas não são iguais.', $dados);
        }

        if (($_POST['aceite'] ?? '') !== '1' || ($_POST['responsavel'] ?? '') !== '1') {
            $this->erroCadastro('É preciso aceitar a Política de Privacidade e confirmar a autorização do responsável (ou ter 18 anos).', $dados);
        }

        $matricula = new Matricula();

        if ($matricula->nomeExisteNaTurma($turmaId, $nome)) {
            $this->erroCadastro(
                'Já existe um(a) "' . $nome . '" nessa turma. Use seu nome completo para diferenciar.',
                $dados
            );
        }

        $usuarios = new User();
        $alunoId = $usuarios->criarAluno($nome, $senha);
        $usuarios->registrarAceite($alunoId, \App\Services\Legal::VERSAO);
        $matricula->inscrever($turmaId, $alunoId);

        Logger::activity('aluno.registered', [
            'user_id' => $alunoId,
            'entity_type' => 'user',
            'entity_id' => $alunoId,
            'description' => "Autocadastro de aluno na turma {$turmaId}: {$nome}",
        ]);

        Auth::tentarLoginAluno($turmaId, $nome, $senha);

        $_SESSION['flash'] = 'Cadastro feito. Bem-vindo(a), ' . $nome . '!';
        $this->redirect('/aluno');
    }

    public function sair(): void
    {
        Auth::iniciarSessao();

        if (!Csrf::valido($_POST[Csrf::CAMPO] ?? null)) {
            $this->redirect('/aluno');
        }

        Auth::logout();
        $this->redirect('/aluno/login');
    }

    private function voltarLogin(string $erro, array $dados): void
    {
        $_SESSION['aluno_login_erro'] = $erro;
        $_SESSION['aluno_login_dados'] = $dados;
        $this->redirect('/aluno/login');
    }

    private function erroCadastro(string $erro, array $dados): void
    {
        $_SESSION['aluno_cadastro_erro'] = $erro;
        $_SESSION['aluno_cadastro_dados'] = $dados;
        $this->redirect('/aluno/cadastro');
    }
}
