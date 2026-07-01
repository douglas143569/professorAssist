<?php

namespace App\Core;

class Request
{
    /**
     * Retorna o IP do cliente, considerando proxies confiaveis
     * (ex: proxy reverso / Cloudflare). Limitado a 45 chars (IPv6).
     */
    public static function ip(): ?string
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // proxy padrao
            'REMOTE_ADDR',           // conexao direta
        ];

        foreach ($candidates as $key) {
            if (!empty($_SERVER[$key])) {
                // X-Forwarded-For pode vir como "cliente, proxy1, proxy2"
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return substr($ip, 0, 45);
                }
            }
        }

        return null;
    }

    public static function userAgent(): ?string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        return $ua ? substr($ua, 0, 255) : null;
    }
}
