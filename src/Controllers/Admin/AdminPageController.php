<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Page;
use TinyShop\Services\Auth;
use TinyShop\Services\Validation;
use TinyShop\Services\View;

/**
 * Admin CMS page management controller.
 *
 * @since 1.0.0
 */
final class AdminPageController
{
    use JsonResponder;

    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly Page $pageModel,
        private readonly Validation $validation,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Render the pages listing.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function pages(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/admin/pages.tpl', [
            'page_title'  => 'Pages',
            'active_page' => 'pages',
            'pages_list'  => $this->pageModel->findAll(),
        ]);
    }

    /**
     * Render the create/edit page form.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function pageForm(Request $request, Response $response, array $args = []): Response
    {
        $page = null;
        $isEdit = false;

        if (!empty($args['id'])) {
            $page = Page::find((int) $args['id']);
            if (!$page) {
                return $response->withHeader('Location', '/admin/pages')->withStatus(302);
            }
            $isEdit = true;
        }

        return $this->view->render($response, 'pages/admin/page_form.tpl', [
            'page_title'  => $isEdit ? 'Edit Page' : 'New Page',
            'active_page' => 'pages',
            'page_data'   => $page,
            'is_edit'     => $isEdit,
        ]);
    }

    /**
     * Return all pages as JSON.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function listPages(Request $request, Response $response): Response
    {
        return $this->json($response, ['success' => true, 'pages' => $this->pageModel->findAll()]);
    }

    /**
     * Create a new CMS page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function createPage(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $title = trim($data['title'] ?? '');

        if ($title === '') {
            return $this->json($response, ['error' => true, 'message' => 'Page title is required'], 422);
        }

        $slug = trim($data['slug'] ?? '');
        if ($slug === '') {
            $slug = $this->validation->slug($title);
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $this->json($response, ['error' => true, 'message' => 'Permalink can only contain lowercase letters, numbers, and hyphens'], 422);
        }
        if (Page::exists('slug',$slug)) {
            return $this->json($response, ['error' => true, 'message' => 'A page with this permalink already exists'], 422);
        }

        $id = $this->pageModel->create([
            'title'            => $title,
            'slug'             => $slug,
            'content'          => $data['content'] ?? '',
            'meta_description' => trim($data['meta_description'] ?? ''),
            'is_published'     => isset($data['is_published']) ? (int) (bool) $data['is_published'] : 1,
        ]);

        $this->logger->info('admin.page_created', [
            'admin_id' => $this->auth->userId(),
            'page_id'  => $id,
        ]);

        return $this->json($response, ['success' => true, 'page' => Page::find($id)], 201);
    }

    /**
     * Update a CMS page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function updatePage(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $page = Page::find($id);

        if (!$page) {
            return $this->json($response, ['error' => true, 'message' => 'Page not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $updates = [];

        if (isset($data['title'])) {
            $title = trim($data['title']);
            if ($title === '') {
                return $this->json($response, ['error' => true, 'message' => 'Page title is required'], 422);
            }
            $updates['title'] = $title;
        }

        if (isset($data['slug'])) {
            $slug = trim($data['slug']);
            if ($slug === '') {
                $slug = $this->validation->slug($updates['title'] ?? $page['title']);
            }
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                return $this->json($response, ['error' => true, 'message' => 'Permalink can only contain lowercase letters, numbers, and hyphens'], 422);
            }
            if (Page::exists('slug',$slug, $id)) {
                return $this->json($response, ['error' => true, 'message' => 'A page with this permalink already exists'], 422);
            }
            $updates['slug'] = $slug;
        }

        if (isset($data['content'])) {
            $updates['content'] = $data['content'];
        }
        if (isset($data['meta_description'])) {
            $updates['meta_description'] = trim($data['meta_description']);
        }
        if (isset($data['is_published'])) {
            $updates['is_published'] = (int) (bool) $data['is_published'];
        }

        if (!empty($updates)) {
            $this->pageModel->update($id, $updates);
        }

        $this->logger->info('admin.page_updated', [
            'admin_id' => $this->auth->userId(),
            'page_id'  => $id,
        ]);

        return $this->json($response, ['success' => true, 'page' => Page::find($id)]);
    }

    /**
     * Delete a CMS page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function deletePage(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $page = Page::find($id);

        if (!$page) {
            return $this->json($response, ['error' => true, 'message' => 'Page not found'], 404);
        }

        $this->pageModel->delete($id);

        $this->logger->info('admin.page_deleted', [
            'admin_id' => $this->auth->userId(),
            'page_id'  => $id,
            'title'    => $page['title'],
        ]);

        return $this->json($response, ['success' => true]);
    }
}
