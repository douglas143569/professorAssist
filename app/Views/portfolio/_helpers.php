<?php
/** Funcoes de apresentacao do Portfolio (carregadas uma vez por requisicao). */

if (!function_exists('pf_h')) {
    function pf_h(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    /** dd/mm/aaaa a partir de uma data SQL (ou vazio). */
    function pf_data(?string $d): string
    {
        return $d ? date('d/m/Y', strtotime($d)) : '—';
    }

    function pf_mes(string $ym): string
    {
        $nomes = ['01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
            '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'];
        [$a, $m] = explode('-', $ym) + [1 => '01'];

        return ($nomes[$m] ?? $m) . ' de ' . $a;
    }

    function pf_midia_url(int $id): string
    {
        return '/creche/portfolio/midias/' . $id;
    }

    /** Foto de perfil ou iniciais coloridas. */
    function pf_avatar(array $c, string $classe = ''): string
    {
        if (!empty($c['foto_path'])) {
            return '<img class="pf-avatar ' . $classe . '" src="/creche/portfolio/' . (int) $c['id'] . '/foto" alt="Foto de ' . pf_h($c['nome']) . '" loading="lazy">';
        }
        $partes = preg_split('/\s+/', trim((string) $c['nome'])) ?: [''];
        $ini = mb_strtoupper(mb_substr($partes[0], 0, 1) . (count($partes) > 1 ? mb_substr(end($partes), 0, 1) : ''));
        $cor = (crc32((string) $c['id']) % 6) + 1;

        return '<span class="pf-avatar pf-avatar--ini pf-cor' . $cor . ' ' . $classe . '" aria-hidden="true">' . pf_h($ini) . '</span>';
    }

    /** Barra de progresso acessivel. */
    function pf_barra(int $pct): string
    {
        return '<div class="pf-bar" role="progressbar" aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100" aria-label="Portfólio '
            . $pct . '% completo"><span style="width:' . $pct . '%"></span></div>';
    }

    /** Tag <textarea> com assistente de IA acoplado (ver portfolio.js). */
    function pf_area_texto(string $name, string $valor, array $o = []): string
    {
        $attrs = '';
        foreach (['data-ia' => $o['ia'] ?? '', 'data-foco' => $o['foco'] ?? '', 'data-crianca' => $o['crianca'] ?? '', 'data-fonte' => $o['fonte'] ?? ''] as $k => $v) {
            if ($v !== '' && $v !== null) {
                $attrs .= ' ' . $k . '="' . pf_h($v) . '"';
            }
        }

        return '<textarea name="' . pf_h($name) . '" id="' . pf_h($o['id'] ?? $name) . '" rows="' . (int) ($o['rows'] ?? 4) . '" maxlength="'
            . (int) ($o['max'] ?? 6000) . '" placeholder="' . pf_h($o['ph'] ?? '') . '"' . ($o['req'] ?? false ? ' required' : '') . $attrs . '>'
            . pf_h($valor) . '</textarea>';
    }

    /** Miniatura/visualizador de uma midia (foto, video ou arquivo). */
    function pf_midia(array $m, bool $excluir = false): string
    {
        $url = pf_midia_url((int) $m['id']);
        $leg = pf_h($m['legenda'] ?: $m['nome_original']);

        if (in_array($m['tipo'], ['foto', 'producao'], true)) {
            $html = '<a class="pf-thumb" href="' . $url . '" data-lightbox data-leg="' . $leg . '"><img src="' . $url . '" alt="' . $leg . '" loading="lazy">'
                . ($m['tipo'] === 'producao' ? '<em class="pf-thumb__tag">Produção</em>' : '') . '</a>';
        } elseif ($m['tipo'] === 'video') {
            $html = '<div class="pf-thumb pf-thumb--video"><video src="' . $url . '" controls preload="metadata" aria-label="' . $leg . '"></video></div>';
        } else {
            $html = '<a class="pf-thumb pf-thumb--file" href="' . $url . '" download><span aria-hidden="true">📎</span><b>' . $leg . '</b><small>Baixar arquivo</small></a>';
        }

        if ($excluir) {
            $html = '<div class="pf-thumbwrap">' . $html
                . '<form method="post" action="/creche/portfolio/midias/' . (int) $m['id'] . '/excluir" onsubmit="return confirm(\'Remover este arquivo?\');">'
                . \App\Services\Csrf::campo() . '<button class="pf-thumb__x" type="submit" aria-label="Remover arquivo">×</button></form></div>';
        }

        return $html;
    }
}
