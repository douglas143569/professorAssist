<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    /**
     * MVP sem login: garante um professor demo e devolve seus dados.
     * Substituir quando houver autenticacao real (RF-01).
     */
    public function professorDemo(): array
    {
        $stmt = $this->db->prepare('SELECT id, name, email FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => 'professor@demo.local']);
        $user = $stmt->fetch();

        if ($user) {
            return $user;
        }

        $insert = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)'
        );
        $insert->execute([
            'name' => 'Professor Demo',
            'email' => 'professor@demo.local',
            'hash' => password_hash('demo', PASSWORD_DEFAULT),
        ]);

        return [
            'id' => (int) $this->db->lastInsertId(),
            'name' => 'Professor Demo',
            'email' => 'professor@demo.local',
        ];
    }
}
