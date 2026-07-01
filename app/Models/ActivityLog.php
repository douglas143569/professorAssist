<?php

namespace App\Models;

use App\Core\Model;

class ActivityLog extends Model
{
    /**
     * Insere um registro de auditoria de negocio.
     * Use preferencialmente via App\Services\Logger::activity().
     */
    public function create(array $data): void
    {
        $sql = 'INSERT INTO activity_logs
                    (user_id, action, entity_type, entity_id, description, properties, ip_address, user_agent)
                VALUES
                    (:user_id, :action, :entity_type, :entity_id, :description, :properties, :ip_address, :user_agent)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'action' => $data['action'],
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'description' => $data['description'] ?? null,
            'properties' => isset($data['properties'])
                ? json_encode($data['properties'], JSON_UNESCAPED_UNICODE)
                : null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }

    /**
     * Lista as atividades mais recentes (para telas de auditoria).
     */
    public function latest(int $limit = 50): array
    {
        $limit = max(1, min($limit, 500));
        $stmt = $this->db->query(
            "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT {$limit}"
        );
        return $stmt->fetchAll();
    }
}
