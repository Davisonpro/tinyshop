<?php

declare(strict_types=1);

namespace TinyShop\Enums;

/**
 * Platform user roles.
 *
 * @since 1.0.0
 */
enum UserRole: string
{
    case Admin  = 'admin';
    case Seller = 'seller';
}
