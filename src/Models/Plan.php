<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\FieldType;

class Plan extends Model
{
    protected static array $definition = [
        'table'   => 'plans',
        'primary' => 'id',
        'fields'  => [
            'name'                  => ['type' => FieldType::String, 'required' => true, 'maxLength' => 100],
            'slug'                  => ['type' => FieldType::String, 'required' => true, 'maxLength' => 100],
            'description'           => ['type' => FieldType::Text],
            'price_monthly'         => ['type' => FieldType::Decimal, 'default' => 0],
            'price_yearly'          => ['type' => FieldType::Decimal, 'default' => 0],
            'currency'              => ['type' => FieldType::String, 'maxLength' => 10, 'default' => 'KES'],
            'max_products'          => ['type' => FieldType::Int],
            'allowed_themes'        => ['type' => FieldType::Json],
            'custom_domain_allowed' => ['type' => FieldType::Bool, 'default' => 0],
            'coupons_allowed'       => ['type' => FieldType::Bool, 'default' => 0],
            'features'              => ['type' => FieldType::Json],
            'cta_text'              => ['type' => FieldType::String, 'maxLength' => 100],
            'badge_text'            => ['type' => FieldType::String, 'maxLength' => 50],
            'is_featured'           => ['type' => FieldType::Bool, 'default' => 0],
            'is_default'            => ['type' => FieldType::Bool, 'default' => 0],
            'is_active'             => ['type' => FieldType::Bool, 'default' => 1],
            'sort_order'            => ['type' => FieldType::Int, 'default' => 0],
            'created_at'            => ['type' => FieldType::DateTime],
            'updated_at'            => ['type' => FieldType::DateTime],
        ],
    ];

    public function findAll(): array
    {
        return static::rawQuery(
            'SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
    }

    public function findAllAdmin(): array
    {
        return static::rawQuery('SELECT * FROM plans ORDER BY sort_order ASC, id ASC');
    }

    public function findDefault(): ?array
    {
        $plan = static::findBy('is_default', 1);
        return $plan?->toArray();
    }

    public function create(array $data): int
    {
        // If this plan is being set as default, unset any existing default
        if (!empty($data['is_default'])) {
            static::rawExecute('UPDATE plans SET is_default = 0');
        }

        $plan = new static();
        $plan->fill([
            'name'                  => $data['name'],
            'slug'                  => $data['slug'],
            'description'           => $data['description'] ?? null,
            'price_monthly'         => $data['price_monthly'] ?? 0,
            'price_yearly'          => $data['price_yearly'] ?? 0,
            'currency'              => $data['currency'] ?? 'KES',
            'max_products'          => $data['max_products'] ?? null,
            'allowed_themes'        => $data['allowed_themes'] ?? null,
            'custom_domain_allowed' => $data['custom_domain_allowed'] ?? 0,
            'coupons_allowed'       => $data['coupons_allowed'] ?? 0,
            'features'              => $data['features'] ?? null,
            'cta_text'              => $data['cta_text'] ?? null,
            'badge_text'            => $data['badge_text'] ?? null,
            'is_featured'           => $data['is_featured'] ?? 0,
            'is_default'            => $data['is_default'] ?? 0,
            'is_active'             => $data['is_active'] ?? 1,
            'sort_order'            => $data['sort_order'] ?? 0,
        ]);
        $plan->save();
        return (int) $plan->getId();
    }

    public function update(int $id, array $data): bool
    {
        // If this plan is being set as default, unset any existing default
        if (!empty($data['is_default'])) {
            static::rawExecute('UPDATE plans SET is_default = 0 WHERE id != ?', [$id]);
        }

        $plan = static::find($id);
        if (!$plan) {
            return false;
        }

        $allowed = [
            'name', 'slug', 'description', 'price_monthly', 'price_yearly', 'currency',
            'max_products', 'allowed_themes', 'custom_domain_allowed', 'coupons_allowed',
            'features', 'cta_text', 'badge_text', 'is_featured',
            'is_default', 'is_active', 'sort_order',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $plan->{$field} = $data[$field];
            }
        }

        return $plan->save();
    }

    public function delete(?int $id = null): bool
    {
        $deleteId = $id ?? $this->getId();
        if ($deleteId === null) {
            return false;
        }

        // Only allow deletion if no active subscribers
        if ($this->countSubscribers((int) $deleteId) > 0) {
            return false;
        }

        return parent::delete((int) $deleteId);
    }

    public function countSubscribers(int $planId): int
    {
        return (int) static::rawScalar(
            'SELECT COUNT(*) FROM users WHERE plan_id = ? AND plan_expires_at > NOW()',
            [$planId]
        );
    }

}
