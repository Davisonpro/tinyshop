<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Models\HelpArticle;
use TinyShop\Models\HelpCategory;
use TinyShop\Services\View;

final class HelpController
{
    public function __construct(
        private readonly View $view,
        private readonly HelpCategory $categoryModel,
        private readonly HelpArticle $articleModel
    ) {}

    public function index(Request $request, Response $response): Response
    {
        $categories = $this->categoryModel->findAll();
        $grouped = $this->articleModel->grouped();
        $allArticles = $this->articleModel->findPublished();

        // Build categories as slug => [name, icon, description] for template
        $catMap = [];
        foreach ($categories as $cat) {
            $catMap[$cat['slug']] = [
                'name'          => $cat['name'],
                'icon'          => $cat['icon'],
                'description'   => $cat['description'],
                'article_count' => $cat['article_count'],
            ];
        }

        return $this->view->render($response, 'pages/help.tpl', [
            'page_title'       => 'Help Center',
            'meta_description' => 'Find answers to common questions about setting up and running your shop.',
            'current_page'     => 'help',
            'categories'       => $catMap,
            'articles_grouped' => $grouped,
            'articles_all'     => $allArticles,
        ]);
    }

    public function article(Request $request, Response $response, array $args): Response
    {
        $slug = $args['slug'] ?? '';

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $response->withStatus(404);
        }

        $article = $this->articleModel->findBySlug($slug);
        if ($article === null) {
            return $response->withStatus(404);
        }

        $category = [
            'name' => $article['category_name'],
            'icon' => $article['category_icon'],
        ];
        $categorySlug = $article['category_slug'];

        // Get siblings for prev/next navigation
        $siblings = $this->articleModel->findByCategory((int) $article['category_id']);
        $currentIndex = -1;

        foreach ($siblings as $i => $a) {
            if ($a['slug'] === $slug) {
                $currentIndex = $i;
                break;
            }
        }

        $prevArticle = $currentIndex > 0 ? $siblings[$currentIndex - 1] : null;
        $nextArticle = ($currentIndex >= 0 && $currentIndex < count($siblings) - 1)
            ? $siblings[$currentIndex + 1]
            : null;

        $related = [];
        foreach ($siblings as $a) {
            if ($a['slug'] !== $slug) {
                $related[] = $a;
            }
            if (count($related) >= 3) {
                break;
            }
        }

        return $this->view->render($response, 'pages/help_article.tpl', [
            'page_title'       => $article['title'] . ' — Help Center',
            'meta_description' => $article['summary'],
            'current_page'     => 'help',
            'article'          => $article,
            'category'         => $category,
            'category_slug'    => $categorySlug,
            'prev_article'     => $prevArticle,
            'next_article'     => $nextArticle,
            'related'          => $related,
        ]);
    }
}
