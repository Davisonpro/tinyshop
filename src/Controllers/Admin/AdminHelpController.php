<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\HelpArticle;
use TinyShop\Models\HelpCategory;
use TinyShop\Services\Auth;
use TinyShop\Services\Validation;
use TinyShop\Services\View;

/**
 * Admin help center management controller.
 *
 * @since 1.0.0
 */
final class AdminHelpController
{
    use JsonResponder;

    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly HelpCategory $helpCategoryModel,
        private readonly HelpArticle $helpArticleModel,
        private readonly Validation $validation,
        private readonly LoggerInterface $logger,
    ) {}

    // ── Pages ──

    /**
     * Render the help center management page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function help(Request $request, Response $response): Response
    {
        $categories = $this->helpCategoryModel->findAllAdmin();
        $articles = $this->helpArticleModel->findAll();

        return $this->view->render($response, 'pages/admin/help.tpl', [
            'page_title'  => 'Help Center',
            'active_page' => 'help',
            'categories'  => $categories,
            'articles'    => $articles,
        ]);
    }

    /**
     * Render the create/edit article form.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function helpArticleForm(Request $request, Response $response, array $args = []): Response
    {
        $categories = $this->helpCategoryModel->findAllAdmin();
        $article = null;
        $isEdit = false;

        if (!empty($args['id'])) {
            $article = $this->helpArticleModel->findById((int) $args['id']);
            if (!$article) {
                return $response->withHeader('Location', '/admin/help')->withStatus(302);
            }
            $isEdit = true;
        }

        return $this->view->render($response, 'pages/admin/help_article.tpl', [
            'page_title'  => $isEdit ? 'Edit Article' : 'New Article',
            'active_page' => 'help',
            'categories'  => $categories,
            'article'     => $article,
            'is_edit'     => $isEdit,
        ]);
    }

    // ── Category API ──

    /**
     * Return all help categories as JSON.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function listHelpCategories(Request $request, Response $response): Response
    {
        return $this->json($response, ['success' => true, 'categories' => $this->helpCategoryModel->findAllAdmin()]);
    }

    /**
     * Create a help category.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function createHelpCategory(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return $this->json($response, ['error' => true, 'message' => 'Category name is required'], 422);
        }

        $slug = $this->validation->slug($name);
        if (HelpCategory::exists('slug',$slug)) {
            return $this->json($response, ['error' => true, 'message' => 'A category with a similar name already exists'], 422);
        }

        $id = $this->helpCategoryModel->create([
            'name'        => $name,
            'slug'        => $slug,
            'icon'        => trim($data['icon'] ?? '') ?: 'fa-circle-question',
            'description' => trim($data['description'] ?? ''),
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
        ]);

        $this->logger->info('admin.help_category_created', [
            'admin_id'    => $this->auth->userId(),
            'category_id' => $id,
        ]);

        return $this->json($response, ['success' => true, 'category' => HelpCategory::find($id)], 201);
    }

    /**
     * Update a help category.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function updateHelpCategory(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $category = HelpCategory::find($id);

        if (!$category) {
            return $this->json($response, ['error' => true, 'message' => 'Category not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $updates = [];

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name === '') {
                return $this->json($response, ['error' => true, 'message' => 'Category name is required'], 422);
            }
            $updates['name'] = $name;
            $newSlug = $this->validation->slug($name);
            if (HelpCategory::exists('slug',$newSlug, $id)) {
                return $this->json($response, ['error' => true, 'message' => 'A category with a similar name already exists'], 422);
            }
            $updates['slug'] = $newSlug;
        }

        if (isset($data['icon'])) $updates['icon'] = trim($data['icon']) ?: 'fa-circle-question';
        if (isset($data['description'])) $updates['description'] = trim($data['description']);
        if (isset($data['sort_order'])) $updates['sort_order'] = (int) $data['sort_order'];

        if (!empty($updates)) {
            $this->helpCategoryModel->update($id, $updates);
        }

        $this->logger->info('admin.help_category_updated', [
            'admin_id'    => $this->auth->userId(),
            'category_id' => $id,
        ]);

        return $this->json($response, ['success' => true, 'category' => HelpCategory::find($id)]);
    }

    /**
     * Delete a help category.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function deleteHelpCategory(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $category = HelpCategory::find($id);

        if (!$category) {
            return $this->json($response, ['error' => true, 'message' => 'Category not found'], 404);
        }

        if (!$this->helpCategoryModel->delete($id)) {
            return $this->json($response, ['error' => true, 'message' => 'Remove all articles from this category first'], 422);
        }

        $this->logger->info('admin.help_category_deleted', [
            'admin_id'    => $this->auth->userId(),
            'category_id' => $id,
            'name'        => $category['name'],
        ]);

        return $this->json($response, ['success' => true]);
    }

    // ── Article API ──

    /**
     * Return all help articles as JSON.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function listHelpArticles(Request $request, Response $response): Response
    {
        return $this->json($response, ['success' => true, 'articles' => $this->helpArticleModel->findAll()]);
    }

    /**
     * Create a help article.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function createHelpArticle(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $title = trim($data['title'] ?? '');
        $categoryId = (int) ($data['category_id'] ?? 0);

        if ($title === '') {
            return $this->json($response, ['error' => true, 'message' => 'Article title is required'], 422);
        }

        if (!HelpCategory::find($categoryId)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid category'], 422);
        }

        $slug = $this->validation->slug($title);
        if (HelpArticle::exists('slug',$slug)) {
            return $this->json($response, ['error' => true, 'message' => 'An article with a similar title already exists'], 422);
        }

        $id = $this->helpArticleModel->create([
            'category_id'  => $categoryId,
            'title'        => $title,
            'slug'         => $slug,
            'summary'      => trim($data['summary'] ?? ''),
            'content'      => $data['content'] ?? '',
            'keywords'     => trim($data['keywords'] ?? ''),
            'sort_order'   => (int) ($data['sort_order'] ?? 0),
            'is_published' => isset($data['is_published']) ? (int) (bool) $data['is_published'] : 1,
        ]);

        $this->logger->info('admin.help_article_created', [
            'admin_id'   => $this->auth->userId(),
            'article_id' => $id,
        ]);

        return $this->json($response, ['success' => true, 'article' => $this->helpArticleModel->findById($id)], 201);
    }

    /**
     * Update a help article.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function updateHelpArticle(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $article = $this->helpArticleModel->findById($id);

        if (!$article) {
            return $this->json($response, ['error' => true, 'message' => 'Article not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $updates = [];

        if (isset($data['title'])) {
            $title = trim($data['title']);
            if ($title === '') {
                return $this->json($response, ['error' => true, 'message' => 'Article title is required'], 422);
            }
            $updates['title'] = $title;
            $newSlug = $this->validation->slug($title);
            if (HelpArticle::exists('slug',$newSlug, $id)) {
                return $this->json($response, ['error' => true, 'message' => 'An article with a similar title already exists'], 422);
            }
            $updates['slug'] = $newSlug;
        }

        if (isset($data['category_id'])) {
            $catId = (int) $data['category_id'];
            if (!HelpCategory::find($catId)) {
                return $this->json($response, ['error' => true, 'message' => 'Invalid category'], 422);
            }
            $updates['category_id'] = $catId;
        }

        if (isset($data['summary'])) $updates['summary'] = trim($data['summary']);
        if (isset($data['content'])) $updates['content'] = $data['content'];
        if (isset($data['keywords'])) $updates['keywords'] = trim($data['keywords']);
        if (isset($data['sort_order'])) $updates['sort_order'] = (int) $data['sort_order'];
        if (isset($data['is_published'])) $updates['is_published'] = (int) (bool) $data['is_published'];

        if (!empty($updates)) {
            $this->helpArticleModel->update($id, $updates);
        }

        $this->logger->info('admin.help_article_updated', [
            'admin_id'   => $this->auth->userId(),
            'article_id' => $id,
        ]);

        return $this->json($response, ['success' => true, 'article' => $this->helpArticleModel->findById($id)]);
    }

    /**
     * Delete a help article.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function deleteHelpArticle(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $article = $this->helpArticleModel->findById($id);

        if (!$article) {
            return $this->json($response, ['error' => true, 'message' => 'Article not found'], 404);
        }

        $this->helpArticleModel->delete($id);

        $this->logger->info('admin.help_article_deleted', [
            'admin_id'   => $this->auth->userId(),
            'article_id' => $id,
            'title'      => $article['title'],
        ]);

        return $this->json($response, ['success' => true]);
    }
}
