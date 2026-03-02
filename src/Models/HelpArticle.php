<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\FieldType;

/**
 * Help article model.
 *
 * @since 1.0.0
 */
final class HelpArticle extends Model
{
    protected static array $definition = [
        'table'   => 'help_articles',
        'primary' => 'id',
        'fields'  => [
            'category_id'  => ['type' => FieldType::Int, 'required' => true],
            'title'        => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'slug'         => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'summary'      => ['type' => FieldType::Text],
            'content'      => ['type' => FieldType::LongText],
            'keywords'     => ['type' => FieldType::String, 'maxLength' => 500],
            'sort_order'   => ['type' => FieldType::Int, 'default' => 0],
            'is_published' => ['type' => FieldType::Bool, 'default' => 1],
            'created_at'   => ['type' => FieldType::DateTime],
            'updated_at'   => ['type' => FieldType::DateTime],
        ],
    ];

    /**
     * Get all published articles with category info.
     *
     * @since 1.0.0
     *
     * @return array[]
     */
    public function findPublished(): array
    {
        return static::rawQuery(
            'SELECT a.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM help_articles a
             JOIN help_categories c ON c.id = a.category_id
             WHERE a.is_published = 1
             ORDER BY c.sort_order ASC, a.sort_order ASC, a.id ASC'
        );
    }

    /**
     * Find a published article by slug.
     *
     * @since 1.0.0
     *
     * @param  string $slug URL slug.
     * @return array|null
     */
    public function findBySlug(string $slug): ?array
    {
        $rows = static::rawQuery(
            'SELECT a.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM help_articles a
             JOIN help_categories c ON c.id = a.category_id
             WHERE a.slug = ? AND a.is_published = 1',
            [$slug]
        );
        return $rows[0] ?? null;
    }

    /**
     * Get published articles in a category.
     *
     * @since 1.0.0
     *
     * @param  int $categoryId Category ID.
     * @return array[]
     */
    public function findByCategory(int $categoryId): array
    {
        return static::rawQuery(
            'SELECT * FROM help_articles
             WHERE category_id = ? AND is_published = 1
             ORDER BY sort_order ASC, id ASC',
            [$categoryId]
        );
    }

    /**
     * Get all articles with category info (admin).
     *
     * @since 1.0.0
     *
     * @return array[]
     */
    public function findAll(): array
    {
        return static::rawQuery(
            'SELECT a.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM help_articles a
             JOIN help_categories c ON c.id = a.category_id
             ORDER BY c.sort_order ASC, a.sort_order ASC, a.id ASC'
        );
    }

    /**
     * Find an article by ID with category info.
     *
     * @since 1.0.0
     *
     * @param  int $id Article ID.
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $rows = static::rawQuery(
            'SELECT a.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM help_articles a
             JOIN help_categories c ON c.id = a.category_id
             WHERE a.id = ?',
            [$id]
        );
        return $rows[0] ?? null;
    }

    /**
     * Create a new article.
     *
     * @since 1.0.0
     *
     * @param  array $data Article data.
     * @return int   New article ID.
     */
    public function create(array $data): int
    {
        $article = new static();
        $article->fill([
            'category_id'  => $data['category_id'],
            'title'        => $data['title'],
            'slug'         => $data['slug'],
            'summary'      => $data['summary'] ?? null,
            'content'      => $data['content'] ?? null,
            'keywords'     => $data['keywords'] ?? null,
            'sort_order'   => $data['sort_order'] ?? 0,
            'is_published' => $data['is_published'] ?? 1,
        ]);
        $article->save();
        return (int) $article->getId();
    }

    /**
     * Update an article by ID.
     *
     * @since 1.0.0
     *
     * @param  int   $id   Article ID.
     * @param  array $data Fields to update.
     * @return bool  False if not found.
     */
    public function update(int $id, array $data): bool
    {
        $article = static::find($id);
        if (!$article) {
            return false;
        }

        $allowed = ['category_id', 'title', 'slug', 'summary', 'content', 'keywords', 'sort_order', 'is_published'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $article->{$field} = $data[$field];
            }
        }

        return $article->save();
    }

    /**
     * Get published articles grouped by category slug.
     *
     * @since 1.0.0
     *
     * @return array<string, array[]>
     */
    public function grouped(): array
    {
        $articles = $this->findPublished();
        $grouped = [];
        foreach ($articles as $article) {
            $catSlug = $article['category_slug'];
            $grouped[$catSlug][] = $article;
        }
        return $grouped;
    }
}
