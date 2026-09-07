<?php

namespace App\Services;

/**
 * Conversor de Markdown -> HTML minimo e seguro.
 *
 * Usado para exibir o texto gerado pela IA (conteudo, plano, resumo) de forma
 * profissional, sem mostrar as marcacoes cruas. NAO e um parser completo: cobre
 * o subconjunto que a IA produz (titulos, listas, negrito, italico, citacao).
 *
 * Seguranca: todo o texto e escapado com htmlspecialchars ANTES de qualquer
 * formatacao. Nenhum HTML do texto original passa. Links so com esquema seguro.
 */
class Markdown
{
    public static function render(?string $texto): string
    {
        $texto = (string) $texto;
        if (trim($texto) === '') {
            return '';
        }

        // Normaliza quebras de linha e escapa TUDO de uma vez.
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');

        $linhas = explode("\n", $texto);
        $html = [];
        $tipoLista = null;          // 'ul' | 'ol' | null
        $paragrafo = [];
        $citacao = [];

        $fechaParagrafo = static function () use (&$paragrafo, &$html) {
            if ($paragrafo !== []) {
                $html[] = '<p>' . self::inline(implode(' ', $paragrafo)) . '</p>';
                $paragrafo = [];
            }
        };
        $fechaLista = static function () use (&$tipoLista, &$html) {
            if ($tipoLista !== null) {
                $html[] = '</' . $tipoLista . '>';
                $tipoLista = null;
            }
        };
        $fechaCitacao = static function () use (&$citacao, &$html) {
            if ($citacao !== []) {
                $html[] = '<blockquote>' . self::inline(implode(' ', $citacao)) . '</blockquote>';
                $citacao = [];
            }
        };

        foreach ($linhas as $linha) {
            $t = trim($linha);

            // Linha em branco: fecha os blocos abertos.
            if ($t === '') {
                $fechaParagrafo();
                $fechaLista();
                $fechaCitacao();
                continue;
            }

            // Regua horizontal
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', $t)) {
                $fechaParagrafo();
                $fechaLista();
                $fechaCitacao();
                $html[] = '<hr>';
                continue;
            }

            // Titulo
            if (preg_match('/^(#{1,6})\s+(.+)$/', $t, $m)) {
                $fechaParagrafo();
                $fechaLista();
                $fechaCitacao();
                $nivel = min(4, max(2, strlen($m[1]) + 1)); // # vira h2; >=### vira h4
                $html[] = "<h{$nivel}>" . self::inline(trim($m[2])) . "</h{$nivel}>";
                continue;
            }

            // Citacao (o ">" ja foi escapado para "&gt;")
            if (preg_match('/^&gt;\s?(.*)$/', $t, $m)) {
                $fechaParagrafo();
                $fechaLista();
                $citacao[] = $m[1];
                continue;
            }
            $fechaCitacao();

            // Item de lista ordenada
            if (preg_match('/^(\d+)[.)]\s+(.+)$/', $t, $m)) {
                $fechaParagrafo();
                if ($tipoLista !== 'ol') {
                    $fechaLista();
                    $html[] = '<ol>';
                    $tipoLista = 'ol';
                }
                $html[] = '<li>' . self::inline(trim($m[2])) . '</li>';
                continue;
            }

            // Item de lista nao ordenada
            if (preg_match('/^([-*\x{2022}\x{25CF}\x{2013}])\s+(.+)$/u', $t, $m)) {
                $fechaParagrafo();
                if ($tipoLista !== 'ul') {
                    $fechaLista();
                    $html[] = '<ul>';
                    $tipoLista = 'ul';
                }
                $html[] = '<li>' . self::inline(trim($m[2])) . '</li>';
                continue;
            }

            // Texto normal: acumula no paragrafo.
            $fechaLista();
            $paragrafo[] = $t;
        }

        $fechaParagrafo();
        $fechaLista();
        $fechaCitacao();

        return implode("\n", $html);
    }

    /**
     * Formatacao inline sobre texto JA ESCAPADO.
     */
    private static function inline(string $s): string
    {
        // Codigo `...` primeiro (protege o conteudo de outras regras).
        $s = preg_replace_callback('/`([^`]+)`/', static fn ($m) => '<code>' . $m[1] . '</code>', $s);

        // Link [texto](url) -- so esquemas seguros. Aceita um par de parenteses
        // aninhado na URL (ex: .../wiki/Assunto_(desambiguacao)).
        $s = preg_replace_callback(
            '/\[([^\]]+)\]\(([^()\s]*(?:\([^()\s]*\))?[^()\s]*)\)/',
            static function ($m) {
                $url = $m[2];
                $ok = preg_match('#^(https?://|mailto:|/)#i', $url);
                if (!$ok) {
                    return $m[1]; // link suspeito vira texto simples
                }
                return '<a href="' . $url . '" target="_blank" rel="noopener">' . $m[1] . '</a>';
            },
            $s
        );

        // Negrito **...** ou __...__
        $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
        $s = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $s);

        // Italico *...* ou _..._ (evita casar dentro de palavra tipo a_b_c)
        $s = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/s', '<em>$1</em>', $s);
        $s = preg_replace('/(?<![\w_])_(?!\s)(.+?)(?<!\s)_(?![\w_])/s', '<em>$1</em>', $s);

        return $s;
    }
}
