<?php

declare(strict_types=1);

namespace TinyShop\Enums;

enum UserRole: string
{
    case Admin  = 'admin';
    case Seller = 'seller';
}
