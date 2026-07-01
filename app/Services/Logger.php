<?php

namespace App\Services;

use App\Core\Request;
use App\Models\AccessLog;
use App\Models\ActivityLog;
use Throwable;

/**
 * Ponto UNICO de escrita de logs do projeto.
 *
 * Convencao do time: nunca faca INSERT direto em activity_logs / access_logs
 * nem escreva em arquivo na mao. Sempre use os metodos abaixo.
 *
 * Regra de ouro: log NUNCA pode quebrar a aplicacao. Todas as falhas de
 * escrita sao silenciadas (com fallback para arquivo) para nao derrubar
 * a requisicao do usuario.
 */
class Logger
{
    /* ---------------------------------------------------------------------
     |  Logs em ARQUIVO (storage/logs/app-AAAA-MM-DD.log)
     |  Para eventos tecnicos: erros, avisos, informacoes de execucao.
     * -------------------------------------------------------------------*/

    public static function debug(string $message, array $context = []): void
    {
        self::writeFile('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::writeFile('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::writeFile('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::writeFile('ERROR', $message, $context);
    }

    /**
     * Loga uma excecao capturada, com stack trace resumido.
     */
    public static function exception(Throwable $e): void
    {
        self::writeFile('ERROR', $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile() . ':' . $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /* ---------------------------------------------------------------------
     |  Log de AUDITORIA DE NEGOCIO (tabela activity_logs)
     |  Para acoes relevantes do dominio: "usuario publicou um post", etc.
     * -------------------------------------------------------------------*/

    /**
     * @param string      $action      ex: 'post.created', 'order.completed'
     * @param array       $options     entity_type, entity_id, description,
     *                                  properties (array), user_id
     */
    public static function activity(string $action, array $options = []): void
    {
        try {
            (new ActivityLog())->create([
                'user_id' => $options['user_id'] ?? self::currentUserId(),
                'action' => $action,
                'entity_type' => $options['entity_type'] ?? null,
                'entity_id' => $options['entity_id'] ?? null,
                'description' => $options['description'] ?? null,
                'properties' => $options['properties'] ?? null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (Throwable $e) {
            self::exception($e); // fallback para arquivo, sem quebrar
        }
    }

    /* ---------------------------------------------------------------------
     |  Log de SEGURANCA / ACESSO (tabela access_logs)
     |  Para eventos de autenticacao: login, logout, tentativas.
     * -------------------------------------------------------------------*/

    /**
     * @param string   $event   login_success | login_failed | logout | password_reset
     */
    public static function access(string $event, ?string $email = null, ?int $userId = null): void
    {
        try {
            (new AccessLog())->create([
                'user_id' => $userId,
                'email' => $email,
                'event' => $event,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (Throwable $e) {
            self::exception($e);
        }
    }

    /* ---------------------------------------------------------------------
     |  Internos
     * -------------------------------------------------------------------*/

    private static function writeFile(string $level, string $message, array $context = []): void
    {
        try {
            $dir = __DIR__ . '/../../storage/logs';
            $file = $dir . '/app-' . date('Y-m-d') . '.log';

            $line = sprintf(
                "[%s] %s: %s%s%s",
                date('Y-m-d H:i:s'),
                $level,
                $message,
                $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
                PHP_EOL
            );

            file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {
            // Ultimo recurso: nao ha para onde logar. Silencia.
        }
    }

    private static function currentUserId(): ?int
    {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }
        return null;
    }
}
