<?php

namespace App\Models;

use App\Core\Model;

class AccessLog extends Model
{
    /**
     * Registra um evento de autenticacao.
     * Use preferencialmente via App\Services\Logger::access().
     */
    public function create(array $data): void
    {
        $sql = 'INSERT INTO access_logs
                    (user_id, email, event, ip_address, user_agent)
                VALUES
                    (:user_id, :email, :event, :ip_address, :user_agent)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'email' => $data['email'] ?? null,
            'event' => $data['event'],
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }

    /**
     * Conta tentativas de login falhas de um IP dentro de uma janela de tempo.
     * Base para rate limiting / bloqueio de brute force.
     */
    public function failedAttemptsFromIp(string $ip, int $minutes = 15): int
    {
        $sql = "SELECT COUNT(*) FROM access_logs
                WHERE event = 'login_failed'
                  AND ip_address = :ip
                  AND created_at >= (NOW() - INTERVAL :minutes MINUTE)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('ip', $ip);
        $stmt->bindValue('minutes', $minutes, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
