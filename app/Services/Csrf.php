<?php

namespace App\Services;

/**
 * Protecao contra CSRF (Cross-Site Request Forgery).
 *
 * O problema: como a sessao vive num cookie, qualquer pagina aberta noutra
 * aba consegue mandar um POST para este sistema COM a sua sessao junto. Sem
 * token, um formulario escondido num site qualquer conseguiria disparar
 * /escolas/{id}/excluir -- que apaga em cascata turmas, materias, temas,
 * conteudos e questoes.
 *
 * A defesa: todo formulario carrega um valor secreto que so quem realmente
 * abriu a pagina conhece. O site atacante nao consegue ler esse valor (a
 * politica de mesma origem do navegador o impede), entao o POST dele chega
 * sem token e e recusado.
 *
 * A checagem fica centralizada no AppController, e nao em cada acao: assim
 * uma rota POST nova ja nasce protegida.
 */
class Csrf
{
    private const CHAVE = 'csrf_token';
    public const CAMPO = '_token';

    /** Token da sessao atual, criado na primeira vez que e pedido. */
    public static function token(): string
    {
        Auth::iniciarSessao();

        if (empty($_SESSION[self::CHAVE])) {
            $_SESSION[self::CHAVE] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CHAVE];
    }

    /** Campo oculto pronto para colar dentro de um <form method="post">. */
    public static function campo(): string
    {
        return '<input type="hidden" name="' . self::CAMPO . '" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    /**
     * O token recebido confere?
     *
     * Usa hash_equals para comparar em tempo constante: uma comparacao comum
     * com === vaza, pelo tempo de resposta, quantos caracteres iniciais
     * estavam certos.
     */
    public static function valido(mixed $enviado): bool
    {
        Auth::iniciarSessao();

        $guardado = $_SESSION[self::CHAVE] ?? '';

        if ($guardado === '' || !is_string($enviado) || $enviado === '') {
            return false;
        }

        return hash_equals($guardado, $enviado);
    }

    /**
     * Descarta o token. Chamado no login e no logout para que a sessao nova
     * nao herde o token da anterior.
     */
    public static function esquecer(): void
    {
        unset($_SESSION[self::CHAVE]);
    }
}
