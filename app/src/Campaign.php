<?php

namespace Pushbase;

use Pushbase\Database\Database;
use Ramsey\Uuid\Uuid;

class Campaign
{
    public function create(array $data, int $userId): array
    {
        $db = Database::getInstance();

        $db->insert('campaigns', [
            'uuid' => Uuid::uuid4()->toString(),
            'name' => $data['name'],
            'push_title' => $data['push_title'],
            'push_body' => $data['push_body'],
            'push_icon' => $data['push_icon'] ?? null,
            'push_image' => $data['push_image'] ?? null,
            'push_badge' => $data['push_badge'] ?? null,
            'push_requireInteraction' => $data['push_requireInteraction'] ?? false,
            'push_url' => $data['push_url'] ?? null,
            'push_renotify' => $data['push_renotify'] ?? false,
            'push_silent' => $data['push_silent'] ?? false,
            'status' => $data['status'] ?? 'draft',
            'send_at' => $data['send_at'] ?? null,
            'segments' => json_encode($data['segments']) ?? null,
            'created_by' => $userId,
            'created_at' => $db->sqleval('NOW()'),
            'updated_by' => $userId,
            'updated_at' => $db->sqleval('NOW()')
        ]);
        $campaignId = $db->insertId();

        return $db->queryFirstRow("SELECT * FROM campaigns WHERE id = %i", $campaignId);
    }

    public function get(string $id): ?array
    {
        $db = Database::getInstance();

        return $db->queryFirstRow(
            "SELECT * FROM campaigns WHERE id = %i OR uuid = %s",
            $id,
            $id
        );
    }

    public function update(string $id, array $data, int $userId): ?array
    {
        $db = Database::getInstance();

        $campaign = $this->get($id);
        if (!$campaign || !in_array($campaign['status'], ['draft', 'scheduled', 'cancelled'])) {
            return null;
        }

        $updateData = [
            'updated_by' => $userId,
            'updated_at' => $db->sqleval('NOW()')
        ];

        foreach ($data as $field => $value) {
            $updateData[$field] = $value;
        }

        $db->update('campaigns', $updateData, 'id=%i', $campaign['id']);
        return $this->get($campaign['id']);
    }

    public function delete(string $id): bool
    {
        $campaign = $this->get($id);
        if (!$campaign || !in_array($campaign['status'], ['draft', 'cancelled'])) {
            return false;
        }

        $db = Database::getInstance();

        $db->delete('campaigns', 'id=%i', $campaign['id']);
        return true;
    }
}
