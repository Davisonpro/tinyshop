<?php

declare(strict_types=1);

namespace TinyShop\Enums;

enum OrderStatus: string
{
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Cancelled = 'cancelled';
    case Refunded  = 'refunded';
}
