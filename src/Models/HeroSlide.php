<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class HeroSlide
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM hero_slides WHERE user_id = ? ORDER BY position ASC, id ASC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM hero_slides WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO hero_slides (user_id, image_url, heading, subheading, link_url, link_text, position, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['image_url'],
            $data['heading'] ?? null,
            $data['subheading'] ?? null,
            $data['link_url'] ?? null,
            $data['link_text'] ?? null,
            $data['position'] ?? 0,
            $data['is_active'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        $allowed = ['image_url', 'heading', 'subheading', 'link_url', 'link_text', 'position', 'is_active'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = 'UPDATE hero_slides SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM hero_slides WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function reorder(int $userId, array $ids): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE hero_slides SET position = ? WHERE id = ? AND user_id = ?'
        );

        foreach ($ids as $position => $id) {
            $stmt->execute([$position, (int) $id, $userId]);
        }

        return true;
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM hero_slides WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}
