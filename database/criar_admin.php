<?php

/**
 * Cria (ou atualiza) a conta ADMINISTRADORA do sistema.
 *
 * A senha NUNCA fica escrita neste arquivo nem em migration -- este projeto
 * e um repositorio publico. Ela e digitada na hora de rodar o comando.
 *
 * Uso:
 *   php database/criar_admin.php "Nome Completo" email@dominio.com
 *   php database/criar_admin.php "Nome Completo" email@dominio.com senha
 *
 * Se ainda existir o "Professor Demo" (a conta placeholder do MVP, dona de
 * todo o conteudo criado ate agora), ele e CONVERTIDO na conta administradora
 * em vez de criar uma conta nova -- assim nada do que ja foi produzido se perde.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script so roda pela linha de comando.');
}

require __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

const EMAIL_DEMO = 'professor@demo.local';

$nome = $argv[1] ?? '';
$email = mb_strtolower(trim($argv[2] ?? ''));
$senha = $argv[3] ?? '';

if ($nome === '' || $email === '') {
    exit("Uso: php database/criar_admin.php \"Nome Completo\" email@dominio.com [senha]\n");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("E-mail invalido: {$email}\n");
}

if ($senha === '') {
    echo 'Senha para ' . $email . ': ';
    $senha = trim((string) fgets(STDIN));
}

if (mb_strlen($senha) < 8) {
    exit("A senha precisa ter pelo menos 8 caracteres.\n");
}

$users = new User();

$existente = $users->findByEmail($email);
$demo = $users->findByEmail(EMAIL_DEMO);

if ($existente !== null) {
    $users->promoverParaAdmin((int) $existente['id'], $nome, $email, $senha);
    $id = (int) $existente['id'];
    $acao = 'Conta ja existia: senha e dados atualizados, papel definido como admin.';
} elseif ($demo !== null) {
    $users->promoverParaAdmin((int) $demo['id'], $nome, $email, $senha);
    $id = (int) $demo['id'];
    $acao = 'O "Professor Demo" virou esta conta -- todo o conteudo dele foi preservado.';
} else {
    $id = $users->create($nome, $email, $senha, User::ROLE_ADMIN);
    $acao = 'Conta administradora criada do zero.';
}

echo "\n";
echo "  {$acao}\n\n";
echo "  id    : {$id}\n";
echo "  nome  : {$nome}\n";
echo "  e-mail: {$email}\n";
echo "  papel : admin\n";
echo "  senha : (guardada como hash bcrypt; nao fica em texto puro)\n\n";
echo "  Entre em http://127.0.0.1:8000/login\n\n";
