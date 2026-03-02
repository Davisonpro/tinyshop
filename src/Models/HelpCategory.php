<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\FieldType;

/**
 * Help category model.
 *
 * @since 1.0.0
 */
final class HelpCategory extends Model
{
    protected static array $definition = [
        'table'   => 'help_categories',
        'primary' => 'id',
        'fields'  => [
            'name'        => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'slug'        => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'icon'        => ['type' => FieldType::String, 'maxLength' => 100, 'default' => 'fa-circle-question'],
            'description' => ['type' => FieldType::Text],
            'sort_order'  => ['type' => FieldType::Int, 'default' => 0],
            'created_at'  => ['type' => FieldType::DateTime],
            'updated_at'  => ['type' => FieldType::DateTime],
        ],
    ];

    /**
     * Get all categories with published article counts.
     *
     * @since 1.0.0
     *
     * @return array[]
     */
    public function findAll(): array
    {
        return static::rawQuery(
            'SELECT c.*, COUNT(a.id) AS article_count
             FROM help_categories c
             LEFT JOIN help_articles a ON a.category_id = c.id AND a.is_published = 1
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.id ASC'
        );
    }

    /**
     * Get all categories with total article counts (admin).
     *
     * @since 1.0.0
     *
     * @return array[]
     */
    public function findAllAdmin(): array
    {
        return static::rawQuery(
            'SELECT c.*, COUNT(a.id) AS article_count
             FROM help_categories c
             LEFT JOIN help_articles a ON a.category_id = c.id
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.id ASC'
        );
    }

    /**
     * Create a new category.
     *
     * @since 1.0.0
     *
     * @param  array $data Category data.
     * @return int   New category ID.
     */
    public function create(array $data): int
    {
        $cat = new static();
        $cat->fill([
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'icon'        => $data['icon'] ?? 'fa-circle-question',
            'description' => $data['description'] ?? null,
            'sort_order'  => $data['sort_order'] ?? 0,
        ]);
        $cat->save();
        return (int) $cat->getId();
    }

    /**
     * Update a category by ID.
     *
     * @since 1.0.0
     *
     * @param  int   $id   Category ID.
     * @param  array $data Fields to update.
     * @return bool  False if not found.
     */
    public function update(int $id, array $data): bool
    {
        $cat = static::find($id);
        if (!$cat) {
            return false;
        }

        $allowed = ['name', 'slug', 'icon', 'description', 'sort_order'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $cat->{$field} = $data[$field];
            }
        }

        return $cat->save();
    }

    /**
     * Delete a category if it has no articles.
     *
     * @since 1.0.0
     *
     * @param  ?int $id Category ID.
     * @return bool False if category has articles or not found.
     */
    public function delete(?int $id = null): bool
    {
        $deleteId = $id ?? $this->getId();
        if ($deleteId === null) {
            return false;
        }

        $count = (int) static::rawScalar(
            'SELECT COUNT(*) FROM help_articles WHERE category_id = ?',
            [$deleteId]
        );
        if ($count > 0) {
            return false;
        }

        return parent::delete((int) $deleteId);
    }

}
