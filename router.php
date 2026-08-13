<?php
/**
 * Router de desenvolvimento para o servidor embutido do PHP.
 *
 * O `php -S` não conhece o .htaccess, então não sabe mandar URLs como
 * /disciplinas para o front controller. Este script serve arquivos que
 * existem em public/ e joga todo o resto para public/index.php.
 *
 * Uso: php -S 127.0.0.1:8000 -t public router.php
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/public' . $path;

if ($path !== '/' && is_file($file)) {
    return false; // deixa o servidor embutido entregar o arquivo estático
}

require __DIR__ . '/public/index.php';
