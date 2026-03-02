<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Plan;
use TinyShop\Models\Subscription;
use TinyShop\Services\Auth;
use TinyShop\Services\Theme;
use TinyShop\Services\Validation;
use TinyShop\Services\View;

/**
 * Admin plan management controller.
 *
 * @since 1.0.0
 */
final class AdminPlanController
{
    use JsonResponder;

    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly Plan $planModel,
        private readonly Subscription $subscriptionModel,
        private readonly Theme $themeService,
        private readonly Validation $validation,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Render the plans management page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function plans(Request $request, Response $response): Response
    {
        $plans = $this->planModel->findAllAdmin();

        foreach ($plans as &$plan) {
            $plan['subscriber_count'] = $this->planModel->countSubscribers((int) $plan['id']);
        }
        unset($plan);

        return $this->view->render($response, 'pages/admin/plans.tpl', [
            'page_title'        => 'Plans',
            'active_page'       => 'plans',
            'plans'             => $plans,
            'available_themes'  => $this->themeService->listAvailable(),
        ]);
    }

    /**
     * Return all plans as JSON.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function listPlans(Request $request, Response $response): Response
    {
        $plans = $this->planModel->findAllAdmin();
        foreach ($plans as &$plan) {
            $plan['subscriber_count'] = $this->planModel->countSubscribers((int) $plan['id']);
        }
        unset($plan);

        return $this->json($response, ['success' => true, 'plans' => $plans]);
    }

    /**
     * Create a plan.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function createPlan(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return $this->json($response, ['error' => true, 'message' => 'Plan name is required'], 422);
        }

        $slug = $this->validation->slug($name);
        if (Plan::exists('slug',$slug)) {
            return $this->json($response, ['error' => true, 'message' => 'A plan with a similar name already exists'], 422);
        }

        $allowedThemes = null;
        if (isset($data['allowed_themes']) && $data['allowed_themes'] !== 'all') {
            $themes = is_array($data['allowed_themes']) ? $data['allowed_themes'] : [$data['allowed_themes']];
            $allowedThemes = json_encode(array_values(array_filter($themes)));
        }

        $features = null;
        if (isset($data['features']) && is_array($data['features'])) {
            $features = json_encode(array_values(array_filter(
                array_map('trim', $data['features']),
                fn($f) => $f !== ''
            )));
        }

        $id = $this->planModel->create([
            'name'                   => $name,
            'slug'                   => $slug,
            'description'            => trim($data['description'] ?? ''),
            'price_monthly'          => (float) ($data['price_monthly'] ?? 0),
            'price_yearly'           => (float) ($data['price_yearly'] ?? 0),
            'currency'               => $data['currency'] ?? 'KES',
            'max_products'           => isset($data['max_products']) && $data['max_products'] !== '' ? (int) $data['max_products'] : null,
            'allowed_themes'         => $allowedThemes,
            'custom_domain_allowed'  => !empty($data['custom_domain_allowed']) ? 1 : 0,
            'coupons_allowed'        => !empty($data['coupons_allowed']) ? 1 : 0,
            'features'               => $features,
            'cta_text'               => trim($data['cta_text'] ?? '') ?: null,
            'badge_text'             => trim($data['badge_text'] ?? '') ?: null,
            'is_featured'            => !empty($data['is_featured']) ? 1 : 0,
            'is_default'             => !empty($data['is_default']) ? 1 : 0,
            'is_active'              => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
            'sort_order'             => (int) ($data['sort_order'] ?? 0),
        ]);

        $plan = Plan::find($id);

        $this->logger->info('admin.plan_created', [
            'admin_id' => $this->auth->userId(),
            'plan_id'  => $id,
            'name'     => $name,
        ]);

        return $this->json($response, ['success' => true, 'plan' => $plan], 201);
    }

    /**
     * Update a plan.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function updatePlan(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $plan = Plan::find($id);

        if (!$plan) {
            return $this->json($response, ['error' => true, 'message' => 'Plan not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $updates = [];

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name === '') {
                return $this->json($response, ['error' => true, 'message' => 'Plan name is required'], 422);
            }
            $updates['name'] = $name;
            $newSlug = $this->validation->slug($name);
            if (Plan::exists('slug',$newSlug, $id)) {
                return $this->json($response, ['error' => true, 'message' => 'A plan with a similar name already exists'], 422);
            }
            $updates['slug'] = $newSlug;
        }

        if (isset($data['description'])) $updates['description'] = trim($data['description']);
        if (isset($data['price_monthly'])) $updates['price_monthly'] = (float) $data['price_monthly'];
        if (isset($data['price_yearly'])) $updates['price_yearly'] = (float) $data['price_yearly'];
        if (isset($data['currency'])) $updates['currency'] = $data['currency'];

        if (array_key_exists('max_products', $data)) {
            $updates['max_products'] = ($data['max_products'] !== '' && $data['max_products'] !== null) ? (int) $data['max_products'] : null;
        }

        if (isset($data['allowed_themes'])) {
            if ($data['allowed_themes'] === 'all' || $data['allowed_themes'] === null) {
                $updates['allowed_themes'] = null;
            } else {
                $themes = is_array($data['allowed_themes']) ? $data['allowed_themes'] : [$data['allowed_themes']];
                $updates['allowed_themes'] = json_encode(array_values(array_filter($themes)));
            }
        }

        if (isset($data['custom_domain_allowed'])) $updates['custom_domain_allowed'] = !empty($data['custom_domain_allowed']) ? 1 : 0;
        if (isset($data['coupons_allowed'])) $updates['coupons_allowed'] = !empty($data['coupons_allowed']) ? 1 : 0;

        if (isset($data['features'])) {
            if (is_array($data['features'])) {
                $updates['features'] = json_encode(array_values(array_filter(
                    array_map('trim', $data['features']),
                    fn($f) => $f !== ''
                )));
            } else {
                $updates['features'] = null;
            }
        }
        if (isset($data['cta_text'])) $updates['cta_text'] = trim($data['cta_text']) ?: null;
        if (isset($data['badge_text'])) $updates['badge_text'] = trim($data['badge_text']) ?: null;
        if (isset($data['is_featured'])) $updates['is_featured'] = !empty($data['is_featured']) ? 1 : 0;

        if (isset($data['is_default'])) $updates['is_default'] = !empty($data['is_default']) ? 1 : 0;
        if (isset($data['is_active'])) $updates['is_active'] = (int) (bool) $data['is_active'];
        if (isset($data['sort_order'])) $updates['sort_order'] = (int) $data['sort_order'];

        if (!empty($updates)) {
            $this->planModel->update($id, $updates);
        }

        $plan = Plan::find($id);

        $this->logger->info('admin.plan_updated', [
            'admin_id' => $this->auth->userId(),
            'plan_id'  => $id,
        ]);

        return $this->json($response, ['success' => true, 'plan' => $plan]);
    }

    /**
     * Delete a plan.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function deletePlan(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $plan = Plan::find($id);

        if (!$plan) {
            return $this->json($response, ['error' => true, 'message' => 'Plan not found'], 404);
        }

        if (!empty($plan['is_default'])) {
            return $this->json($response, ['error' => true, 'message' => 'Cannot delete the default plan'], 422);
        }

        if (!$this->planModel->delete($id)) {
            return $this->json($response, ['error' => true, 'message' => 'Cannot delete a plan with active subscribers'], 422);
        }

        $this->logger->info('admin.plan_deleted', [
            'admin_id' => $this->auth->userId(),
            'plan_id'  => $id,
            'name'     => $plan['name'],
        ]);

        return $this->json($response, ['success' => true]);
    }
}
