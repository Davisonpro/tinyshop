<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\FieldType;

class Category extends Model
{
    protected static array $definition = [
        'table'   => 'categories',
        'primary' => 'id',
        'fields'  => [
            'user_id'    => ['type' => FieldType::Int, 'required' => true],
            'parent_id'  => ['type' => FieldType::Int],
            'name'       => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'slug'       => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'image_url'  => ['type' => FieldType::String, 'maxLength' => 500],
            'sort_order' => ['type' => FieldType::Int, 'default' => 0],
            'created_at' => ['type' => FieldType::DateTime],
            'updated_at' => ['type' => FieldType::DateTime],
        ],
    ];

    public function findByUser(int $userId): array
    {
        return static::rawQuery(
            'SELECT * FROM categories WHERE user_id = ? ORDER BY sort_order ASC, name ASC',
            [$userId]
        );
    }

    public function findByUserAndSlug(int $userId, string $slug): ?array
    {
        $rows = static::rawQuery(
            'SELECT * FROM categories WHERE user_id = ? AND slug = ?',
            [$userId, $slug]
        );
        return $rows[0] ?? null;
    }

    /**
     * Find a category by name (case-insensitive) under a specific parent.
     * Tries exact name match first, then slug match as fallback.
     */
    public function findByUserNameAndParent(int $userId, string $name, ?int $parentId): ?array
    {
        // Exact name match (case-insensitive) under this parent
        $sql = 'SELECT * FROM categories WHERE user_id = ? AND LOWER(name) = LOWER(?)';
        $params = [$userId, trim($name)];

        if ($parentId === null) {
            $sql .= ' AND parent_id IS NULL';
        } else {
            $sql .= ' AND parent_id = ?';
            $params[] = $parentId;
        }

        $rows = static::rawQuery($sql . ' LIMIT 1', $params);
        if (!empty($rows)) {
            return $rows[0];
        }

        // Slug match under this parent
        $slug = self::generateSlug($name);
        $sql2 = 'SELECT * FROM categories WHERE user_id = ? AND slug = ?';
        $params2 = [$userId, $slug];

        if ($parentId === null) {
            $sql2 .= ' AND parent_id IS NULL';
        } else {
            $sql2 .= ' AND parent_id = ?';
            $params2[] = $parentId;
        }

        $rows2 = static::rawQuery($sql2 . ' LIMIT 1', $params2);
        if (!empty($rows2)) {
            return $rows2[0];
        }

        // Last resort: name match anywhere for this user (ignore parent)
        $rows3 = static::rawQuery(
            'SELECT * FROM categories WHERE user_id = ? AND LOWER(name) = LOWER(?) LIMIT 1',
            [$userId, trim($name)]
        );
        return $rows3[0] ?? null;
    }

    public function findByUserAsTree(int $userId): array
    {
        $all = $this->findByUser($userId);
        $byParent = [];
        foreach ($all as $cat) {
            $pid = $cat['parent_id'] ?? null;
            $byParent[$pid ?? 0][] = $cat;
        }

        $tree = [];
        foreach (($byParent[0] ?? []) as $parent) {
            $parent['children'] = $byParent[$parent['id']] ?? [];
            $tree[] = $parent;
        }
        return $tree;
    }

    public function create(array $data): int
    {
        $slug = $data['slug'] ?? self::generateSlug($data['name']);
        $slug = $this->ensureUniqueSlug((int) $data['user_id'], $slug);

        $cat = new static();
        $cat->fill([
            'user_id'    => $data['user_id'],
            'parent_id'  => $data['parent_id'] ?? null,
            'name'       => $data['name'],
            'slug'       => $slug,
            'image_url'  => $data['image_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $cat->save();
        return (int) $cat->getId();
    }

    public function update(int $id, array $data): bool
    {
        // Auto-generate slug when name changes
        $cat = static::find($id);
        if (!$cat) {
            return false;
        }

        // Auto-generate slug when name changes
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = self::generateSlug($data['name']);
            $data['slug'] = $this->ensureUniqueSlug((int) $cat->user_id, $data['slug'], $id);
        }

        $allowed = ['name', 'slug', 'image_url', 'sort_order', 'parent_id'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $cat->{$field} = $data[$field];
            }
        }

        return $cat->save();
    }

    public static function generateSlug(string $name): string
    {
        $slug = mb_strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-') ?: 'category';
    }

    private function ensureUniqueSlug(int $userId, string $slug, ?int $excludeId = null): string
    {
        $base = $slug;
        $counter = 1;

        while (true) {
            $sql = 'SELECT id FROM categories WHERE user_id = ? AND slug = ?';
            $params = [$userId, $slug];

            if ($excludeId !== null) {
                $sql .= ' AND id != ?';
                $params[] = $excludeId;
            }

            $found = static::rawScalar($sql, $params);

            if ($found === false) {
                return $slug;
            }

            $slug = $base . '-' . (++$counter);
        }
    }
}
