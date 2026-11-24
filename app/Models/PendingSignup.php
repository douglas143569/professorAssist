<?php

namespace App\Models;

use App\Core\Model;

class PendingSignup extends Model
{
    public const VALIDITY_MINUTES = 15;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;

    public function create(
        array $data,
        string $passwordHash,
        string $codeHash,
        string $consentVersion,
        ?string $ip
    ): void {
        $this->db->exec('DELETE FROM pending_signups WHERE expires_at <= NOW()');

        $stmt = $this->db->prepare(
            'INSERT INTO pending_signups
                (name, email, celular, password_hash, code_hash, aceite_versao,
                 attempts, expires_at, last_sent_at, ip_address)
             VALUES
                (:name, :email, :celular, :password_hash, :code_hash, :consent_version,
                 0, DATE_ADD(NOW(), INTERVAL ' . self::VALIDITY_MINUTES . ' MINUTE), NOW(), :ip)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                celular = VALUES(celular),
                password_hash = VALUES(password_hash),
                code_hash = VALUES(code_hash),
                aceite_versao = VALUES(aceite_versao),
                attempts = 0,
                expires_at = VALUES(expires_at),
                last_sent_at = NOW(),
                ip_address = VALUES(ip_address)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'celular' => $data['celular'],
            'password_hash' => $passwordHash,
            'code_hash' => $codeHash,
            'consent_version' => $consentVersion,
            'ip' => $ip,
        ]);
    }

    public function findForConfirmation(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT name, email, celular, password_hash, code_hash, aceite_versao,
                    attempts, expires_at, (expires_at > NOW()) AS nao_expirado
               FROM pending_signups
              WHERE email = :email
              LIMIT 1
              FOR UPDATE'
        );
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function registerFailedAttempt(string $email): void
    {
        $stmt = $this->db->prepare(
            'UPDATE pending_signups SET attempts = attempts + 1 WHERE email = :email'
        );
        $stmt->execute(['email' => $email]);
    }

    public function consume(string $email): void
    {
        $stmt = $this->db->prepare('DELETE FROM pending_signups WHERE email = :email');
        $stmt->execute(['email' => $email]);
    }

    /** @return array{status:string, seconds?:int} */
    public function resend(string $email, string $code): array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT TIMESTAMPDIFF(SECOND, last_sent_at, NOW()) AS elapsed
                   FROM pending_signups
                  WHERE email = :email
                  LIMIT 1
                  FOR UPDATE'
            );
            $stmt->execute(['email' => $email]);
            $pending = $stmt->fetch();

            if (!$pending) {
                $this->db->commit();
                return ['status' => 'missing'];
            }

            $elapsed = (int) $pending['elapsed'];
            if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
                $this->db->commit();
                return [
                    'status' => 'cooldown',
                    'seconds' => self::RESEND_COOLDOWN_SECONDS - $elapsed,
                ];
            }

            $update = $this->db->prepare(
                'UPDATE pending_signups
                    SET code_hash = :code_hash,
                        attempts = 0,
                        expires_at = DATE_ADD(NOW(), INTERVAL ' . self::VALIDITY_MINUTES . ' MINUTE),
                        last_sent_at = NOW()
                  WHERE email = :email'
            );
            $update->execute([
                'code_hash' => password_hash($code, PASSWORD_DEFAULT),
                'email' => $email,
            ]);
            $this->db->commit();
            return ['status' => 'sent'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function delete(string $email): void
    {
        $stmt = $this->db->prepare('DELETE FROM pending_signups WHERE email = :email');
        $stmt->execute(['email' => $email]);
    }
}
