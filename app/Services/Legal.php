<?php

namespace App\Services;

/**
 * Dados legais exibidos nas politicas (LGPD). Tudo que identifica a empresa vem
 * do .env para nao ficar fixo no codigo; sem configuracao, usa um texto neutro.
 */
class Legal
{
    /** Mude quando as politicas mudarem de forma relevante: pede novo aceite no futuro. */
    public const VERSAO = '2026-11-17';
    public const ATUALIZADO_EM = '17/11/2026';

    public static function controlador(): string
    {
        return self::env('LGPD_CONTROLADOR') ?? 'Assistente do Professor';
    }

    public static function cnpj(): ?string
    {
        return self::env('LGPD_CNPJ');
    }

    /** E-mail de contato publico. Pode ser trocado por LGPD_CONTATO_EMAIL no .env. */
    public const CONTATO_PADRAO = 'contato@profassitent.com.br';

    public static function contatoEmail(): ?string
    {
        return self::env('LGPD_CONTATO_EMAIL') ?? self::CONTATO_PADRAO;
    }

    public static function encarregado(): ?string
    {
        return self::env('LGPD_ENCARREGADO');
    }

    private static function env(string $chave): ?string
    {
        $v = trim((string) ($_ENV[$chave] ?? ''));
        return $v !== '' ? $v : null;
    }
}
