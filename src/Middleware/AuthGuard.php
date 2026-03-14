<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use TinyShop\Enums\UserRole;
use TinyShop\Models\User;
use TinyShop\Services\Auth;

/**
 * Authenticated-user route protection.
 *
 * Supports two authentication strategies:
 *  1. Bearer token — stateless, used by the mobile app.
 *  2. PHP session  — stateful, used by the web dashboard.
 *
 * Bearer tokens take precedence. When a valid bearer token is found the
 * user identity is hydrated into the session so that downstream code
 * (controllers, services) can use Auth::userId() without changes.
 *
 * @since 1.0.0
 */
final class AuthGuard implements MiddlewareInterface
{
    private const ACTIVE_CHECK_INTERVAL = 60;
    private const BEARER_TOKEN_LENGTH   = 64;

    public function __construct(
        private readonly Auth $auth,
        private readonly User $userModel,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Strategy 1: Bearer token (mobile app)
        $user = $this->resolveBearer($request);
        if ($user !== null) {
            return $this->handleBearerAuth($request, $handler, $user);
        }

        // Strategy 2: PHP session (web dashboard)
        return $this->handleSessionAuth($request, $handler);
    }

    // ── Bearer token authentication ──────────────────────────

    private function resolveBearer(ServerRequestInterface $request): ?array
    {
        $header = $request->getHeaderLine('Authorization');
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);
        if (strlen($token) !== self::BEARER_TOKEN_LENGTH) {
            return null;
        }

        return $this->userModel->findByApiToken($token);
    }

    private function handleBearerAuth(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        array $user,
    ): ResponseInterface {
        if (isset($user['is_active']) && (int) $user['is_active'] === 0) {
            return $this->deny($request, 'Account suspended');
        }

        // Hydrate session so downstream code can use Auth::userId() etc.
        $this->hydrateSession($user);

        return $handler->handle($request);
    }

    private function hydrateSession(array $user): void
    {
        Auth::ensureSession();

        $role = UserRole::tryFrom($user['role'] ?? '') ?? UserRole::Seller;

        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['user_name'] = $user['store_name'] ?? '';
        $_SESSION['user_role'] = $role->value;
        $_SESSION['created']   = time();
    }

    // ── Session-based authentication ─────────────────────────

    private function handleSessionAuth(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        Auth::ensureSession();

        if (!$this->auth->check()) {
            return $this->deny($request);
        }

        if (!$this->auth->isAdmin() && !$this->auth->isImpersonating() && $this->isSuspended()) {
            $this->auth->logout();
            return $this->deny($request, 'Account suspended');
        }

        return $handler->handle($request);
    }

    private function isSuspended(): bool
    {
        $userId = $this->auth->userId();
        if ($userId === null) {
            return false;
        }

        $lastCheck = $_SESSION['_active_checked_at'] ?? 0;
        if (time() - $lastCheck < self::ACTIVE_CHECK_INTERVAL) {
            return !($_SESSION['_is_active'] ?? true);
        }

        $active = $this->userModel->isActive($userId);
        $_SESSION['_active_checked_at'] = time();
        $_SESSION['_is_active']         = $active;

        return !$active;
    }

    // ── Shared helpers ───────────────────────────────────────

    private function deny(ServerRequestInterface $request, string $message = 'Authentication required'): ResponseInterface
    {
        if (str_starts_with($request->getUri()->getPath(), '/api/')) {
            $status = $message === 'Authentication required' ? 401 : 403;
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error'   => true,
                'message' => $message,
            ]));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }

        return (new Response())->withHeader('Location', '/login')->withStatus(302);
    }
}
