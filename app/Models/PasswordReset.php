<?php

namespace App\Models;

use App\Core\Model;

class PasswordReset extends Model
{
    public const VALIDADE_MINUTOS = 60;
    public const MAX_POR_USUARIO_HORA = 3;
    public const MAX_POR_IP_HORA = 8;

    public function pedidosRecentesDoUsuario(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM password_resets WHERE user_id = :u AND created_at > (NOW() - INTERVAL 1 HOUR)'
        );
        $stmt->execute(['u' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function pedidosRecentesDoIp(?string $ip): int
    {
        if ($ip === null) {
            return 0;
        }
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM password_resets WHERE ip_address = :ip AND created_at > (NOW() - INTERVAL 1 HOUR)'
        );
        $stmt->execute(['ip' => $ip]);
        return (int) $stmt->fetchColumn();
    }

    /** Cria o pedido e devolve o token PURO (vai so no e-mail; no banco fica o hash). */
    public function criar(int $userId, ?string $ip): string
    {
        $token = bin2hex(random_bytes(32));
        $stmt = $this->db->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, ip_address)
             VALUES (:u, :h, NOW() + INTERVAL ' . self::VALIDADE_MINUTOS . ' MINUTE, :ip)'
        );
        $stmt->execute(['u' => $userId, 'h' => hash('sha256', $token), 'ip' => $ip]);
        return $token;
    }

    /** Pedido valido (existe, nao expirou, nao foi usado) com o usuario ativo. */
    public function validoPorToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $stmt = $this->db->prepare(
            'SELECT r.id, r.user_id, u.email, u.name
               FROM password_resets r JOIN users u ON u.id = r.user_id
              WHERE r.token_hash = :h AND r.used_at IS NULL AND r.expires_at > NOW()
                AND u.ativo = 1 AND u.email IS NOT NULL
              LIMIT 1'
        );
        $stmt->execute(['h' => hash('sha256', $token)]);
        return $stmt->fetch() ?: null;
    }

    /** Marca o pedido como usado e invalida todos os outros do usuario. */
    public function consumir(int $resetId, int $userId): void
    {
        $this->db->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id')->execute(['id' => $resetId]);
        $this->db->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :u AND used_at IS NULL')->execute(['u' => $userId]);
    }
}
