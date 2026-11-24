<?php
// Diagnostico do Mercado Pago: php scripts/mp_check.php
// Valida o token, informa o modo (teste/producao) e cria uma preferencia de exemplo.

require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

use App\Services\MercadoPago;

$ok = fn(string $m) => print("[OK]   $m\n");
$no = function (string $m) { print("[ERRO] $m\n"); exit(1); };

$token = trim((string) ($_ENV['MERCADO_PAGO_ACCESS_TOKEN'] ?? ''));
if ($token === '') {
    $no('MERCADO_PAGO_ACCESS_TOKEN vazio no .env. Cole o Access Token de teste (TEST-...).');
}

try {
    $mp = new MercadoPago();
    $me = $mp->whoAmI();
    $ok('Token valido. Conta/vendedor: ' . ($me['nickname'] ?? $me['id'] ?? '?'));
    print('       Modo: ' . ($mp->isTestMode() ? 'TESTE (TEST-)' : 'PRODUCAO (cobrancas reais!)') . "\n");

    $pref = $mp->createCheckoutPreference(
        ['id' => 0, 'name' => 'Teste de integracao', 'price' => 10.00],
        ['email' => 'comprador@example.com'],
        0
    );
    $ok('Preferencia criada: ' . $pref['id']);
    print('       Checkout: ' . $pref['init_point'] . "\n");
} catch (Throwable $e) {
    $no($e->getMessage());
}

print("\nAPP_URL: " . ($_ENV['APP_URL'] ?? '(vazio)') . "\n");
print('Webhook secret: ' . (trim((string) ($_ENV['MERCADO_PAGO_WEBHOOK_SECRET'] ?? '')) !== '' ? "configurado\n" : "NAO configurado (webhook sera recusado com 401)\n"));
print('Public key (cartao): ' . (MercadoPago::publicKey() !== '' ? "configurada\n" : "NAO configurada (so PIX funcionara)\n"));
