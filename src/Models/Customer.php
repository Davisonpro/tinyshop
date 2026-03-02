<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\FieldType;

/**
 * Shop customer model.
 *
 * @since 1.0.0
 */
final class Customer extends Model
{
    protected static array $definition = [
        'table'   => 'customers',
        'primary' => 'id',
        'fields'  => [
            'user_id'       => ['type' => FieldType::Int, 'required' => true],
            'name'          => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'email'         => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'phone'         => ['type' => FieldType::String, 'maxLength' => 50],
            'password_hash' => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'last_login_at' => ['type' => FieldType::DateTime],
            'login_count'   => ['type' => FieldType::Int, 'default' => 0],
            'created_at'    => ['type' => FieldType::DateTime],
            'updated_at'    => ['type' => FieldType::DateTime],
        ],
    ];

    /**
     * Find a customer by email within a shop.
     *
     * @since 1.0.0
     *
     * @param  int    $shopId Shop (seller) ID.
     * @param  string $email  Customer email.
     * @return array|null
     */
    public function findByShopAndEmail(int $shopId, string $email): ?array
    {
        $result = static::findWhere(['user_id' => $shopId, 'email' => $email]);
        return $result?->toArray();
    }

    /**
     * Check if an email is already registered in a shop.
     *
     * @since 1.0.0
     *
     * @param  int    $shopId    Shop (seller) ID.
     * @param  string $email     Customer email.
     * @param  ?int   $excludeId Customer ID to exclude.
     * @return bool
     */
    public function emailExists(int $shopId, string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM customers WHERE user_id = ? AND email = ?';
        $params = [$shopId, $email];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return static::rawScalar($sql, $params) !== false;
    }

    /**
     * Create a new customer account.
     *
     * @since 1.0.0
     *
     * @param  array $data Customer data.
     * @return int   New customer ID.
     */
    public function create(array $data): int
    {
        $customer = new static();
        $customer->fill([
            'user_id'       => $data['user_id'],
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'password_hash' => $data['password_hash'],
        ]);
        $customer->save();
        return (int) $customer->getId();
    }

    /**
     * Update a customer's profile.
     *
     * @since 1.0.0
     *
     * @param  int   $id   Customer ID.
     * @param  array $data Fields to update.
     * @return bool  False if not found.
     */
    public function update(int $id, array $data): bool
    {
        $customer = static::find($id);
        if (!$customer) {
            return false;
        }

        $allowed = ['name', 'email', 'phone'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $customer->{$field} = $data[$field];
            }
        }

        return $customer->save();
    }

    /**
     * Update a customer's password hash.
     *
     * @since 1.0.0
     *
     * @param  int    $id   Customer ID.
     * @param  string $hash New bcrypt hash.
     * @return bool
     */
    public function updatePassword(int $id, string $hash): bool
    {
        return static::rawExecute(
            'UPDATE customers SET password_hash = ? WHERE id = ?',
            [$hash, $id]
        ) > 0;
    }

    /**
     * Record a customer login.
     *
     * @since 1.0.0
     *
     * @param int $id Customer ID.
     */
    public function recordLogin(int $id): void
    {
        static::increment($id, 'login_count');
        static::rawExecute(
            'UPDATE customers SET last_login_at = NOW() WHERE id = ?',
            [$id]
        );
    }
}
