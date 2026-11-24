<?php
/**
 * Pagamento dentro do sistema: PIX e cartao de credito (Mercado Pago, Checkout Transparente).
 *
 * Variaveis: $plan, $price, $promotion, $configured, $testMode, $publicKey, $userEmail.
 */
use App\Services\Csrf;

$h = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$brl = static fn ($v) => number_format((float) $v, 2, ',', '.');
$dias = (int) $plan['duration_days'];
$temCartao = $configured && $publicKey !== '';
?>
<link rel="stylesheet" href="/assets/css/assinatura.css">
<link rel="stylesheet" href="/assets/css/pagamento.css">

<div class="pg" id="pg"
     data-plan="<?= (int) $plan['id'] ?>"
     data-promo="<?= $promotion ? (int) $promotion['id'] : '' ?>"
     data-price="<?= $h(number_format($price, 2, '.', '')) ?>"
     data-key="<?= $h($publicKey) ?>"
     data-token="<?= $h(Csrf::token()) ?>">

    <a class="pg-back" href="/assinatura">← Voltar aos planos</a>

    <?php if ($testMode): ?>
        <div class="pg-test">
            <b>Modo de teste:</b> nenhuma cobrança real será feita. Cartão de teste:
            <code>5031 4332 1540 6351</code>, validade <code>11/30</code>, CVV <code>123</code>, titular <code>APRO</code>,
            CPF <code>12345678909</code>.
        </div>
    <?php endif; ?>

    <?php if (!$configured): ?>
        <div class="pg-alert">O pagamento ainda não foi configurado (falta o Access Token do Mercado Pago). Avise o administrador.</div>
    <?php endif; ?>

    <div class="pg-grid">
        <section class="pg-card" id="pg-main">
            <h2>Como você quer pagar?</h2>

            <div class="pg-tabs" role="tablist">
                <button type="button" class="pg-tab is-on" data-tab="pix" role="tab">
                    PIX <small>aprovação na hora</small>
                </button>
                <button type="button" class="pg-tab" data-tab="cartao" role="tab">
                    Cartão de crédito <small>até 12x</small>
                </button>
            </div>

            <label class="pg-lbl" for="pg-cpf">CPF do pagador</label>
            <input class="pg-in" id="pg-cpf" inputmode="numeric" autocomplete="off" placeholder="000.000.000-00" maxlength="14">

            <!-- ===== PIX ===== -->
            <div class="pg-pane" id="pane-pix">
                <div id="pix-start">
                    <p class="pg-hint">Gere o QR Code, pague pelo app do seu banco e o acesso é liberado automaticamente.</p>
                    <button type="button" class="as-btn as-btn--solid" id="btn-pix" <?= $configured ? '' : 'disabled' ?>>Gerar PIX de R$ <?= $brl($price) ?></button>
                </div>

                <div id="pix-show" hidden>
                    <div class="pix-qr"><img id="pix-img" alt="QR Code PIX" width="220" height="220"></div>
                    <p class="pg-hint">Abra o app do seu banco, escolha <b>Pagar com PIX</b> e escaneie o QR Code, ou use o código abaixo.</p>
                    <div class="pix-copy">
                        <input class="pg-in" id="pix-code" readonly>
                        <button type="button" class="as-btn" id="btn-copy">Copiar</button>
                    </div>
                    <p class="pix-wait"><span class="pix-dot"></span> Aguardando o pagamento… <span id="pix-timer"></span></p>
                </div>
            </div>

            <!-- ===== CARTAO ===== -->
            <div class="pg-pane" id="pane-cartao" hidden>
                <?php if (!$temCartao): ?>
                    <div class="pg-alert">Pagamento com cartão indisponível: configure <code>MERCADO_PAGO_PUBLIC_KEY</code> no <code>.env</code>. O PIX continua funcionando.</div>
                <?php else: ?>
                    <label class="pg-lbl">Número do cartão</label>
                    <div class="pg-field" id="f-numero"></div>

                    <div class="pg-row">
                        <div><label class="pg-lbl">Validade</label><div class="pg-field" id="f-validade"></div></div>
                        <div><label class="pg-lbl">CVV</label><div class="pg-field" id="f-cvv"></div></div>
                    </div>

                    <label class="pg-lbl" for="c-nome">Nome impresso no cartão</label>
                    <input class="pg-in" id="c-nome" autocomplete="cc-name" placeholder="Como está no cartão">

                    <label class="pg-lbl" for="c-parcelas">Parcelas</label>
                    <select class="pg-in" id="c-parcelas"><option value="1">1x de R$ <?= $brl($price) ?></option></select>

                    <button type="button" class="as-btn as-btn--solid" id="btn-cartao">Pagar R$ <?= $brl($price) ?></button>
                <?php endif; ?>
            </div>

            <p class="pg-msg" id="pg-msg" role="alert" hidden></p>

            <div class="pg-ok" id="pg-ok" hidden>
                <div class="pg-ok__ico">✓</div>
                <h3>Pagamento confirmado!</h3>
                <p>Seu acesso está liberado. Redirecionando…</p>
                <a class="as-btn as-btn--solid" href="/assinatura">Ir para minha assinatura</a>
            </div>
        </section>

        <aside class="pg-card pg-sum">
            <h3>Resumo do pedido</h3>
            <dl>
                <dt>Plano</dt><dd><?= $h($plan['name']) ?></dd>
                <dt>Acesso</dt><dd><?= $dias ?> dias</dd>
                <?php if ($promotion): ?>
                    <dt>Preço do plano</dt><dd><s>R$ <?= $brl($plan['price']) ?></s></dd>
                    <dt>Sua promoção</dt><dd class="ok">aplicada</dd>
                <?php endif; ?>
            </dl>
            <p class="pg-total"><span>Total</span><strong>R$ <?= $brl($price) ?></strong></p>
            <p class="pg-sec">🔒 Pagamento seguro processado pelo Mercado Pago. Não guardamos os dados do seu cartão.</p>

            <form method="post" action="/assinatura/checkout" class="pg-alt">
                <?= Csrf::campo() ?>
                <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                <?php if ($promotion): ?><input type="hidden" name="promotion_id" value="<?= (int) $promotion['id'] ?>"><?php endif; ?>
                <button type="submit" class="pg-link">Prefere boleto ou outras formas? Pagar no site do Mercado Pago</button>
            </form>
        </aside>
    </div>
</div>

<?php if ($temCartao): ?>
<script src="https://sdk.mercadopago.com/js/v2"></script>
<?php endif; ?>
<script src="/assets/js/pagamento.js"></script>
