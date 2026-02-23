<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\FieldType;

class Page extends Model
{
    protected static array $definition = [
        'table'   => 'pages',
        'primary' => 'id',
        'fields'  => [
            'title'            => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'slug'             => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'content'          => ['type' => FieldType::LongText],
            'meta_description' => ['type' => FieldType::String, 'maxLength' => 500],
            'is_published'     => ['type' => FieldType::Bool, 'default' => 1],
            'created_at'       => ['type' => FieldType::DateTime],
            'updated_at'       => ['type' => FieldType::DateTime],
        ],
    ];

    public function findAll(): array
    {
        return static::rawQuery('SELECT * FROM pages ORDER BY title ASC');
    }

    public function findPublished(): array
    {
        return static::rawQuery(
            'SELECT * FROM pages WHERE is_published = 1 ORDER BY title ASC'
        );
    }

    public function findBySlug(string $slug): ?array
    {
        $rows = static::rawQuery(
            'SELECT * FROM pages WHERE slug = ? AND is_published = 1',
            [$slug]
        );
        return $rows[0] ?? null;
    }

    public function create(array $data): int
    {
        $page = new static();
        $page->fill([
            'title'            => $data['title'],
            'slug'             => $data['slug'],
            'content'          => $data['content'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'is_published'     => $data['is_published'] ?? 1,
        ]);
        $page->save();
        return (int) $page->getId();
    }

    public function update(int $id, array $data): bool
    {
        $page = static::find($id);
        if (!$page) {
            return false;
        }

        $allowed = ['title', 'slug', 'content', 'meta_description', 'is_published'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $page->{$field} = $data[$field];
            }
        }

        return $page->save();
    }

}
