<?php

namespace App\Services;

use RuntimeException;
use Throwable;

/**
 * Envio de e-mail por SMTP, sem dependencias extras.
 *
 * Configure no .env: MAIL_HOST, MAIL_PORT (587 = STARTTLS, 465 = SSL),
 * MAIL_ENCRYPTION (tls|ssl|none), MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM,
 * MAIL_FROM_NAME.
 *
 * Sem MAIL_HOST: em desenvolvimento a mensagem e gravada em
 * storage/logs/mail-AAAA-MM-DD.log (para testar o link); em producao NADA e
 * gravado (o e-mail pode ter link de redefinicao) e o envio falha com erro.
 */
class Mailer
{
    public static function configurado(): bool
    {
        return trim((string) ($_ENV['MAIL_HOST'] ?? '')) !== '';
    }

    /** @return bool true se enviou (ou gravou no log de desenvolvimento). */
    public static function enviar(string $para, string $assunto, string $html, string $texto): bool
    {
        $para = self::limpar($para);
        $assunto = self::limpar($assunto);

        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!self::configurado()) {
            if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
                Logger::error('E-mail nao enviado: MAIL_HOST nao configurado em producao.');
                return false;
            }
            $linha = '[' . date('c') . "] PARA: {$para}\nASSUNTO: {$assunto}\n{$texto}\n" . str_repeat('-', 60) . "\n";
            $dir = dirname(__DIR__, 2) . '/storage/logs';
            @file_put_contents($dir . '/mail-' . date('Y-m-d') . '.log', $linha, FILE_APPEND | LOCK_EX);
            return true;
        }

        try {
            self::smtp($para, $assunto, $html, $texto);
            return true;
        } catch (Throwable $e) {
            Logger::error('Falha ao enviar e-mail: ' . $e->getMessage());
            return false;
        }
    }

    private static function smtp(string $para, string $assunto, string $html, string $texto): void
    {
        $host = trim((string) $_ENV['MAIL_HOST']);
        $enc = strtolower(trim((string) ($_ENV['MAIL_ENCRYPTION'] ?? 'tls')));
        $porta = (int) ($_ENV['MAIL_PORT'] ?? ($enc === 'ssl' ? 465 : 587));
        $usuario = (string) ($_ENV['MAIL_USERNAME'] ?? '');
        $senha = (string) ($_ENV['MAIL_PASSWORD'] ?? '');
        $de = self::limpar((string) ($_ENV['MAIL_FROM'] ?? $usuario));
        $nomeDe = self::limpar((string) ($_ENV['MAIL_FROM_NAME'] ?? 'Assistente do Professor'));

        if (!filter_var($de, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('MAIL_FROM invalido.');
        }

        $alvo = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $porta;
        $contexto = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $s = @stream_socket_client($alvo, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $contexto);
        if (!$s) {
            throw new RuntimeException("SMTP: nao conectou ({$errno} {$errstr})");
        }
        stream_set_timeout($s, 15);

        try {
            self::esperar($s, [220]);
            $ehlo = gethostname() ?: 'localhost';
            self::cmd($s, "EHLO {$ehlo}", [250]);

            if ($enc === 'tls') {
                self::cmd($s, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('SMTP: falha ao iniciar TLS.');
                }
                self::cmd($s, "EHLO {$ehlo}", [250]);
            }

            if ($usuario !== '') {
                self::cmd($s, 'AUTH LOGIN', [334]);
                self::cmd($s, base64_encode($usuario), [334]);
                self::cmd($s, base64_encode($senha), [235]);
            }

            self::cmd($s, "MAIL FROM:<{$de}>", [250]);
            self::cmd($s, "RCPT TO:<{$para}>", [250, 251]);
            self::cmd($s, 'DATA', [354]);

            $limite = 'b' . bin2hex(random_bytes(12));
            $msg = 'Date: ' . date('r') . "\r\n"
                . 'From: =?UTF-8?B?' . base64_encode($nomeDe) . "?= <{$de}>\r\n"
                . "To: <{$para}>\r\n"
                . 'Subject: =?UTF-8?B?' . base64_encode($assunto) . "?=\r\n"
                . 'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . (explode('@', $de)[1] ?? 'localhost') . ">\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: multipart/alternative; boundary=\"{$limite}\"\r\n\r\n"
                . "--{$limite}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($texto))
                . "--{$limite}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($html))
                . "--{$limite}--\r\n";

            // "dot stuffing": linha que comeca com "." ganha outro "." (base64 nao tem, mas por seguranca).
            $msg = preg_replace('/^\./m', '..', $msg);
            fwrite($s, $msg . "\r\n.\r\n");
            self::esperar($s, [250]);
            self::cmd($s, 'QUIT', [221]);
        } finally {
            if (is_resource($s)) {
                fclose($s);
            }
        }
    }

    /** @param resource $s */
    private static function cmd($s, string $comando, array $esperados): void
    {
        fwrite($s, $comando . "\r\n");
        self::esperar($s, $esperados);
    }

    /** @param resource $s */
    private static function esperar($s, array $esperados): void
    {
        $resposta = '';
        while (($linha = fgets($s, 515)) !== false) {
            $resposta .= $linha;
            if (strlen($linha) < 4 || $linha[3] === ' ') {
                break; // ultima linha da resposta (multilinha usa "250-")
            }
        }
        $codigo = (int) substr($resposta, 0, 3);
        if (!in_array($codigo, $esperados, true)) {
            throw new RuntimeException('SMTP: resposta inesperada: ' . trim(substr($resposta, 0, 120)));
        }
    }

    private static function limpar(string $v): string
    {
        return trim(str_replace(["\r", "\n", "\0"], '', $v));
    }
}
