<?php

declare(strict_types=1);

namespace TinyShop\Enums;

/**
 * Order lifecycle states.
 *
 * @since 1.0.0
 */
enum OrderStatus: string
{
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Cancelled = 'cancelled';
    case Refunded  = 'refunded';
}
