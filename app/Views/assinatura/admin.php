<?php
$cards = [
    ['Clientes', (int) $summary['customers']],
    ['Assinaturas ativas', (int) $summary['active_subscriptions']],
    ['Assinaturas vencidas', (int) $summary['expired_subscriptions']],
    ['Receita neste mês', 'R$ ' . number_format((float) $summary['monthly_revenue'], 2, ',', '.')],
    ['Receita neste ano', 'R$ ' . number_format((float) $summary['yearly_revenue'], 2, ',', '.')],
];
?>
<div class="stat-grid">
    <?php foreach ($cards as [$label, $value]): ?>
        <article class="stat"><div class="stat__value"><?= htmlspecialchars((string) $value) ?></div><div class="stat__label"><?= htmlspecialchars($label) ?></div></article>
    <?php endforeach; ?>
</div>

<h3>Acesso dos professores</h3>
<div style="overflow-x:auto">
    <table>
        <thead><tr><th>Professor</th><th>Status do acesso</th><th>Assinatura</th><th>Liberar por dias</th><th>Ação</th></tr></thead>
        <tbody>
        <?php foreach ($professors as $teacher): ?>
            <?php
                $accessStatus = match ($teacher['access_mode']) {
                    'blocked' => 'Bloqueado pelo administrador',
                    'granted' => strtotime($teacher['access_until']) > time()
                        ? 'Liberado até ' . date('d/m/Y', strtotime($teacher['access_until']))
                        : ($teacher['subscription_status'] === 'active' ? 'Assinatura ativa' : 'Sem acesso ativo'),
                    default => $teacher['subscription_status'] === 'active'
                        ? 'Assinatura ativa'
                        : 'Sem acesso ativo',
                };
            ?>
            <tr>
                <td><?= htmlspecialchars($teacher['name']) ?><br><small><?= htmlspecialchars($teacher['email']) ?></small></td>
                <td><?= htmlspecialchars($accessStatus) ?></td>
                <td><?= htmlspecialchars($teacher['plan_name'] ?? '—') ?>
                    <?= !empty($teacher['subscription_ends_at']) ? ' · até ' . htmlspecialchars(date('d/m/Y', strtotime($teacher['subscription_ends_at']))) : '' ?></td>
                <td>
                    <form method="post" action="/admin/financeiro/acesso/liberar" style="display:flex;gap:8px;align-items:center">
                        <?= \App\Services\Csrf::campo() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $teacher['id'] ?>">
                        <input type="number" name="days" min="1" max="3650" value="30" required aria-label="Dias de acesso">
                        <button type="submit" class="btn btn--primary">Liberar</button>
                    </form>
                </td>
                <td>
                    <form method="post" action="/admin/financeiro/acesso/bloquear">
                        <?= \App\Services\Csrf::campo() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $teacher['id'] ?>">
                        <button type="submit" class="btn">Bloquear</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h3>Criar promoção individual</h3>
<form method="post" action="/admin/financeiro/promocoes" class="card" style="max-width:760px">
    <?= \App\Services\Csrf::campo() ?>
    <label for="promotion-user">Professor</label>
    <select id="promotion-user" name="user_id" required>
        <option value="">Selecione</option>
        <?php foreach ($professors as $teacher): ?>
            <option value="<?= (int) $teacher['id'] ?>"><?= htmlspecialchars($teacher['name'] . ' — ' . $teacher['email']) ?></option>
        <?php endforeach; ?>
    </select>
    <label for="promotion-plan">Plano</label>
    <select id="promotion-plan" name="plan_id" required>
        <option value="">Selecione</option>
        <?php foreach ($plans as $plan): ?>
            <option value="<?= (int) $plan['id'] ?>"><?= htmlspecialchars($plan['name']) ?> — preço normal R$ <?= number_format((float) $plan['price'], 2, ',', '.') ?></option>
        <?php endforeach; ?>
    </select>
    <label for="promotion-price">Preço promocional (R$)</label>
    <input id="promotion-price" type="number" name="price" min="0.01" step="0.01" required>
    <label for="promotion-expiry">Válida até</label>
    <input id="promotion-expiry" type="date" name="expires_on" min="<?= htmlspecialchars(date('Y-m-d', strtotime('+1 day'))) ?>" required>
    <button type="submit" class="btn btn--primary">Criar promoção</button>
    <p class="muted">A promoção é vinculada ao professor e plano selecionados e pode ser usada uma vez.</p>
</form>

<h3>Promoções recentes</h3>
<div style="overflow-x:auto">
    <table>
        <thead><tr><th>Professor</th><th>Plano</th><th>Preço promocional</th><th>Validade</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($promotions as $promotion): ?>
            <tr>
                <td><?= htmlspecialchars($promotion['user_name']) ?><br><small><?= htmlspecialchars($promotion['email']) ?></small></td>
                <td><?= htmlspecialchars($promotion['plan_name']) ?></td>
                <td>R$ <?= number_format((float) $promotion['promotional_price'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($promotion['expires_at']))) ?></td>
                <td><?= htmlspecialchars($promotion['status']) ?><?= $promotion['status'] === 'available' && strtotime($promotion['expires_at']) < time() ? ' (expirada)' : '' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h3>Histórico de pagamentos (últimos 200)</h3>
<div style="overflow-x:auto">
    <table>
        <thead><tr><th>Data</th><th>Cliente</th><th>E-mail</th><th>Plano</th><th>Valor</th><th>Método</th><th>Status</th><th>Transação</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($payment['created_at']))) ?></td>
                <td><?= htmlspecialchars($payment['user_name']) ?></td>
                <td><?= htmlspecialchars($payment['email']) ?></td>
                <td><?= htmlspecialchars($payment['plan_name']) ?></td>
                <td>R$ <?= number_format((float) $payment['amount'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($payment['payment_method'] ?? '—') ?></td>
                <td><?= htmlspecialchars($payment['status']) ?></td>
                <td><?= htmlspecialchars($payment['transaction_id'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
