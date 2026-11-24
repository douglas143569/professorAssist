<?php
/**
 * Minha assinatura — escolha de plano e historico.
 *
 * Variaveis: $subscription (ativa ou null), $accessOverride, $plans,
 * $payments, $promotions.
 */
use App\Models\Subscription;
use App\Services\Csrf;

$h = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$brl = static fn ($v) => number_format((float) $v, 2, ',', '.');

$emTeste = $subscription && ($subscription['plan_name'] ?? '') === Subscription::TRIAL_PLAN_NAME;
$assinaturaPaga = $subscription && !$emTeste;
$bloqueado = ($accessOverride['mode'] ?? null) === 'blocked';
$liberado = ($accessOverride['mode'] ?? null) === 'granted';

$diasRestantes = $subscription
    ? max(0, (int) ceil((strtotime($subscription['ends_at']) - time()) / 86400))
    : 0;

// Preco por mes de cada plano e economia frente ao plano mais curto (mensal).
$porMes = static fn (array $p) => (float) $p['price'] / max(1, (int) $p['duration_days'] / 30);
$base = $plans ? $porMes($plans[0]) : 0; // active() ja vem ordenado por duracao
$melhorMes = $plans ? min(array_map($porMes, $plans)) : 0;

// Textos de apoio por duracao do plano (30 / 90 / 365 dias).
$subtitulo = static fn (int $d) => match (true) {
    $d <= 31 => 'Ideal para começar',
    $d <= 100 => 'Mais economia e praticidade',
    default => 'O melhor custo-benefício',
};

$statusPagamento = [
    'approved' => ['Aprovado', 'ok'],
    'pending' => ['Pendente', 'wait'],
    'in_process' => ['Em análise', 'wait'],
    'rejected' => ['Recusado', 'bad'],
    'cancelled' => ['Cancelado', 'bad'],
    'failed' => ['Não concluído', 'bad'],
];

$ico = [
    'shield' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 4.500 3.300 8 8 9 4.700-1 8-4.500 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>',
    'bolt' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/></svg>',
    'cal' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg>',
    'lock' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>',
    'check' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>',
];
?>
<link rel="stylesheet" href="/assets/css/assinatura.css">

<div class="as">

    <!-- ===== Topo: titulo + status atual ===== -->
    <section class="as-hero">
        <div class="as-hero__txt">
            <span class="as-chip">Invista no seu tempo</span>
            <h2>Escolha o plano ideal <br>para a <em>sua jornada</em></h2>
            <p>Tenha acesso a todas as funcionalidades do Assistente do Professor e transforme sua rotina com o poder da Inteligência Artificial.</p>

            <ul class="as-trust">
                <li><span><?= $ico['shield'] ?></span><div><b>Pagamento seguro</b>via Mercado Pago</div></li>
                <li><span><?= $ico['cal'] ?></span><div><b>Sem renovação automática</b>você renova quando quiser</div></li>
                <li><span><?= $ico['bolt'] ?></span><div><b>Acesso imediato</b>após a confirmação</div></li>
                <li><span><?= $ico['lock'] ?></span><div><b>PIX, cartão ou boleto</b>você escolhe</div></li>
            </ul>
        </div>

        <aside class="as-status <?= $bloqueado ? 'is-bad' : ($subscription || $liberado ? 'is-ok' : 'is-off') ?>">
            <?php if ($bloqueado): ?>
                <span class="as-status__lbl">Acesso bloqueado</span>
                <p>O administrador bloqueou este acesso. Entre em contato com o suporte da escola/plataforma.</p>
            <?php elseif ($subscription): ?>
                <span class="as-status__lbl"><?= $emTeste ? 'Teste grátis ativo' : 'Assinatura ativa' ?></span>
                <strong class="as-status__big"><?= $diasRestantes ?> <?= $diasRestantes === 1 ? 'dia restante' : 'dias restantes' ?></strong>
                <p>
                    <?= $emTeste ? 'Seu teste' : 'Plano <b>' . $h($subscription['plan_name']) . '</b>,' ?>
                    vale até <b><?= $h(date('d/m/Y', strtotime($subscription['ends_at']))) ?></b>.
                    <?= $emTeste ? 'Escolha um plano abaixo para continuar sem interrupção.' : 'Renove abaixo para somar mais dias ao seu acesso.' ?>
                </p>
            <?php elseif ($liberado): ?>
                <span class="as-status__lbl">Acesso liberado</span>
                <p>Liberado pelo administrador<?= !empty($accessOverride['access_until']) ? ' até <b>' . $h(date('d/m/Y', strtotime($accessOverride['access_until']))) . '</b>' : '' ?>.</p>
            <?php else: ?>
                <span class="as-status__lbl">Assinatura inativa</span>
                <strong class="as-status__big">Acesso suspenso</strong>
                <p>Seu período acabou. Escolha um plano para liberar o acesso novamente.</p>
            <?php endif; ?>
        </aside>
    </section>

    <!-- ===== Planos + Por que assinar ===== -->
    <section class="as-main">
        <div class="as-plans">
            <?php foreach ($plans as $plan):
                $dias = (int) $plan['duration_days'];
                $destaque = $dias > 31 && $dias <= 100;           // trimestral = recomendado
                $melhor = $porMes($plan) === $melhorMes && count($plans) > 1;
                $economia = $base > 0 ? (int) round((1 - $porMes($plan) / $base) * 100) : 0;

                $promotion = null;
                foreach ($promotions as $candidate) {
                    if ((int) $candidate['plan_id'] === (int) $plan['id']) {
                        $promotion = $candidate;
                        break;
                    }
                }
                $precoTotal = ($promotion && $promotion['status'] !== 'redeemed')
                    ? (float) $promotion['promotional_price'] : (float) $plan['price'];
                $mesExibido = $precoTotal / max(1, $dias / 30);
            ?>
                <article class="as-plan<?= $destaque ? ' as-plan--top' : '' ?>">
                    <?php if ($destaque): ?><div class="as-plan__ribbon">★ Recomendado</div><?php endif; ?>
                    <div class="as-plan__body">
                        <h3><?= $h($plan['name']) ?></h3>
                        <p class="as-plan__sub"><?= $h($subtitulo($dias)) ?></p>

                        <?php if ($promotion && $promotion['status'] === 'available'): ?>
                            <p class="as-plan__old">De R$ <?= $brl($plan['price']) ?></p>
                        <?php endif; ?>
                        <p class="as-plan__preco">R$ <?= $brl($mesExibido) ?><small> /mês</small></p>
                        <p class="as-plan__total">
                            <?php if ($dias <= 31): ?>
                                Cobrança única de R$ <?= $brl($precoTotal) ?>
                            <?php else: ?>
                                R$ <?= $brl($precoTotal) ?> <?= $dias >= 360 ? 'por ano' : 'a cada ' . round($dias / 30) . ' meses' ?>
                            <?php endif; ?>
                        </p>

                        <?php if ($promotion && $promotion['status'] === 'available'): ?>
                            <p class="as-plan__promo">Oferta personalizada até <?= $h(date('d/m/Y', strtotime($promotion['expires_at']))) ?></p>
                        <?php elseif ($promotion && $promotion['status'] === 'reserved'): ?>
                            <p class="as-plan__promo">Promoção aplicada — pagamento pendente</p>
                        <?php endif; ?>

                        <ul class="as-plan__list">
                            <li><?= $ico['check'] ?> Acesso completo a todas as funcionalidades</li>
                            <li><?= $ico['check'] ?> <?= $dias ?> dias de acesso</li>
                            <li><?= $ico['check'] ?> PIX, cartão de crédito ou boleto</li>
                        </ul>
                    </div>

                    <div class="as-plan__foot">
                        <?php if ($promotion && $promotion['status'] === 'reserved'): ?>
                            <?php if (!empty($promotion['checkout_url'])): ?>
                                <a class="as-btn as-btn--solid" href="<?= $h($promotion['checkout_url']) ?>">Continuar pagamento</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a class="as-btn <?= $destaque ? 'as-btn--solid' : '' ?>"
                               href="/assinatura/pagar/<?= (int) $plan['id'] ?><?= $promotion ? '?promocao=' . (int) $promotion['id'] : '' ?>">
                                <?= $promotion ? 'Usar minha promoção' : ($assinaturaPaga ? 'Renovar com este plano' : 'Escolher plano') ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($economia > 0): ?>
                            <p class="as-plan__eco">Economize <b><?= $economia ?>%</b> em relação ao plano mensal</p>
                        <?php elseif ($melhor === false): ?>
                            <p class="as-plan__eco">&nbsp;</p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <aside class="as-why">
            <h3>Por que assinar?</h3>
            <ul>
                <li><?= $ico['check'] ?> Crie atividades, provas e planos de aula em segundos com IA</li>
                <li><?= $ico['check'] ?> Conteúdos alinhados à BNCC</li>
                <li><?= $ico['check'] ?> Organize escolas e turmas em um só lugar</li>
                <li><?= $ico['check'] ?> Você revisa e aprova tudo antes de usar</li>
                <li><?= $ico['check'] ?> Educação Infantil com folhas prontas para imprimir</li>
            </ul>
        </aside>
    </section>

    <!-- ===== Pagamento seguro ===== -->
    <section class="as-pay">
        <div class="as-pay__txt">
            <span class="as-pay__logo"><?= $ico['shield'] ?></span>
            <div>
                <small>Pagamento 100% seguro com</small>
                <strong>Mercado Pago</strong>
                <p>Pague com PIX ou cartão aqui mesmo, sem sair do sistema. Não guardamos dados do seu cartão.</p>
            </div>
        </div>
        <ul class="as-pay__methods">
            <li>PIX</li><li>Cartão de crédito</li><li>Boleto</li>
        </ul>
    </section>

    <!-- ===== Historico ===== -->
    <section class="as-hist">
        <h3>Histórico de pagamentos</h3>
        <?php if (!$payments): ?>
            <p class="as-empty">Ainda não há pagamentos registrados.</p>
        <?php else: ?>
            <div class="as-tablewrap">
                <table class="as-table">
                    <thead><tr><th>Data</th><th>Plano</th><th>Valor</th><th>Método</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($payments as $payment):
                        [$rotulo, $tom] = $statusPagamento[$payment['status']] ?? [ucfirst((string) $payment['status']), 'wait'];
                    ?>
                        <tr>
                            <td><?= $h(date('d/m/Y H:i', strtotime($payment['created_at']))) ?></td>
                            <td><?= $h($payment['plan_name']) ?></td>
                            <td>R$ <?= $brl($payment['amount']) ?></td>
                            <td><?= $h($payment['payment_method'] ?? '—') ?></td>
                            <td><span class="as-pill as-pill--<?= $tom ?>"><?= $h($rotulo) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
