<?php

declare(strict_types=1);

namespace TinyShop\Services;

use TinyShop\Models\Plan;
use TinyShop\Models\Product;
use TinyShop\Models\User;

/**
 * Subscription plan enforcement.
 *
 * @since 1.0.0
 */
final class PlanGuard
{
    private ?array $defaultPlan = null;

    public function __construct(
        private readonly Plan $planModel,
        private readonly Product $productModel,
        private readonly User $userModel
    ) {}

    /**
     * Get the effective plan for a user.
     *
     * @since 1.0.0
     *
     * @param  int $userId User ID.
     * @return array Plan data.
     */
    public function getUserPlan(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return $this->getDefaultPlan();
        }

        $planId = $user['plan_id'] ?? null;
        $expiresAt = $user['plan_expires_at'] ?? null;

        // If user has a plan and it hasn't expired, use it
        if ($planId !== null && $expiresAt !== null) {
            $expired = strtotime($expiresAt) < time();
            if (!$expired) {
                $plan = Plan::find((int) $planId);
                if ($plan) {
                    return $plan->toArray();
                }
            }
        }

        return $this->getDefaultPlan();
    }

    /**
     * Check if the user can create another product.
     *
     * @since 1.0.0
     *
     * @param  int  $userId User ID.
     * @return bool
     */
    public function canCreateProduct(int $userId): bool
    {
        $plan = $this->getUserPlan($userId);
        $maxProducts = $plan['max_products'] ?? null;

        // NULL = unlimited
        if ($maxProducts === null) {
            return true;
        }

        $currentCount = $this->productModel->countByUser($userId);
        return $currentCount < (int) $maxProducts;
    }

    /**
     * Check if the user's plan allows a specific theme.
     *
     * @since 1.0.0
     *
     * @param  int    $userId User ID.
     * @param  string $theme  Theme slug.
     * @return bool
     */
    public function canUseTheme(int $userId, string $theme): bool
    {
        $plan = $this->getUserPlan($userId);
        $allowedThemes = $plan['allowed_themes'] ?? null;

        // NULL = all themes allowed
        if (!is_array($allowedThemes)) {
            return true;
        }

        return in_array($theme, $allowedThemes, true);
    }

    /** Check if the user's plan allows custom domains. */
    public function canUseCustomDomain(int $userId): bool
    {
        $plan = $this->getUserPlan($userId);
        return !empty($plan['custom_domain_allowed']);
    }

    /** Check if the user's plan allows coupons. */
    public function canUseCoupons(int $userId): bool
    {
        $plan = $this->getUserPlan($userId);
        return !empty($plan['coupons_allowed']);
    }

    /**
     * Build a plan usage summary for the dashboard.
     *
     * @since 1.0.0
     *
     * @param  int   $userId User ID.
     * @return array Usage data including limits, counts, and flags.
     */
    public function getUsageSummary(int $userId): array
    {
        $plan = $this->getUserPlan($userId);
        $user = User::find($userId);
        $productCount = $this->productModel->countByUser($userId);

        $maxProducts = $plan['max_products'] ?? null;
        $themes = $plan['allowed_themes'] ?? null;

        $expiresAt = $user['plan_expires_at'] ?? null;
        $daysLeft = null;
        if ($expiresAt !== null) {
            $diff = strtotime($expiresAt) - time();
            $daysLeft = max(0, (int) ceil($diff / 86400));
        }

        $productPercent = 0;
        if ($maxProducts !== null && (int) $maxProducts > 0) {
            $productPercent = min(100, (int) round($productCount / (int) $maxProducts * 100));
        }

        $currentPrice = (float) ($plan['price_monthly'] ?? 0);
        $canUpgrade = false;
        foreach ($this->planModel->findAll() as $p) {
            if ((float) ($p['price_monthly'] ?? 0) > $currentPrice) {
                $canUpgrade = true;
                break;
            }
        }

        return [
            'plan' => $plan,
            'product_count' => $productCount,
            'max_products' => $maxProducts !== null ? (int) $maxProducts : null,
            'products_unlimited' => $maxProducts === null,
            'product_percent' => $productPercent,
            'allowed_themes' => $themes,
            'all_themes' => $themes === null,
            'custom_domain' => !empty($plan['custom_domain_allowed']),
            'coupons' => !empty($plan['coupons_allowed']),
            'is_free' => $currentPrice === 0.0,
            'can_upgrade' => $canUpgrade,
            'expires_at' => $expiresAt,
            'days_left' => $daysLeft,
        ];
    }

    /** Get the default free plan. */
    private function getDefaultPlan(): array
    {
        if ($this->defaultPlan === null) {
            $this->defaultPlan = $this->planModel->findDefault();
            if ($this->defaultPlan === null) {
                // Fallback: no plans in DB — restrictive free tier
                $this->defaultPlan = [
                    'id' => 0,
                    'name' => 'Free',
                    'slug' => 'free',
                    'price_monthly' => 0,
                    'price_yearly' => 0,
                    'max_products' => 10,
                    'allowed_themes' => null,
                    'custom_domain_allowed' => 0,
                    'coupons_allowed' => 0,
                ];
            }
        }
        return $this->defaultPlan;
    }
}
