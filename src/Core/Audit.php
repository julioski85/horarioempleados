<?php

declare(strict_types=1);

namespace App\Core;

final class Audit
{
    public static function log(string $eventType, string $entityType, int $entityId, ?array $oldData, ?array $newData, ?string $reason = null): void
    {
        $user = $_SESSION['user'] ?? null;
        $stmt = Database::connection()->prepare(
            'INSERT INTO audit_log (actor_id, actor_role, event_type, entity_type, entity_id, old_data, new_data, reason, ip_address, user_agent, created_at)
            VALUES (:actor_id, :actor_role, :event_type, :entity_type, :entity_id, :old_data, :new_data, :reason, :ip, :ua, NOW())'
        );
        $stmt->execute([
            'actor_id' => $user['id'] ?? null,
            'actor_role' => $user['role'] ?? 'system',
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_data' => $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
            'new_data' => $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }
}
