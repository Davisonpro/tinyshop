<?php

declare(strict_types=1);

namespace TinyShop\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use TinyShop\Models\User;
use TinyShop\Services\Auth;

/**
 * Authenticated-user route protection.
 *
 * @since 1.0.0
 */
final class AuthGuard implements MiddlewareInterface
{
    /** @var int Seconds between active-status checks. */
    private const ACTIVE_CHECK_INTERVAL = 60;

    public function __construct(
        private readonly Auth $auth,
        private readonly User $userModel
    ) {}

    /**
     * Require an authenticated, non-suspended user.
     *
     * @since 1.0.0
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        Auth::ensureSession();

        if (!$this->auth->check()) {
            return $this->deny($request);
        }

        // Verify seller is still active (skip for admins and impersonation)
        if (!$this->auth->isAdmin() && !$this->auth->isImpersonating()) {
            if ($this->isSuspended()) {
                $this->auth->logout();
                return $this->deny($request, 'Account suspended');
            }
        }

        return $handler->handle($request);
    }

    /** Check if the seller account has been suspended (cached in session). */
    private function isSuspended(): bool
    {
        $userId = $this->auth->userId();
        if ($userId === null) {
            return false;
        }

        // Cache the DB lookup for ACTIVE_CHECK_INTERVAL seconds
        $lastCheck = $_SESSION['_active_checked_at'] ?? 0;
        if (time() - $lastCheck < self::ACTIVE_CHECK_INTERVAL) {
            return !($_SESSION['_is_active'] ?? true);
        }

        $active = $this->userModel->isActive($userId);
        $_SESSION['_active_checked_at'] = time();
        $_SESSION['_is_active'] = $active;

        return !$active;
    }

    /** Build a 401/403 JSON or redirect response. */
    private function deny(ServerRequestInterface $request, string $message = 'Authentication required'): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if (str_starts_with($path, '/api/')) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error'   => true,
                'message' => $message,
            ]));
            $status = $message === 'Authentication required' ? 401 : 403;
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }

        return (new Response())->withHeader('Location', '/login')->withStatus(302);
    }
}
