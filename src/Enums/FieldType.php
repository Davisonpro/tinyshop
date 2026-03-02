<?php

declare(strict_types=1);

namespace TinyShop\Enums;

/**
 * Model field data types.
 *
 * @since 1.0.0
 */
enum FieldType: string
{
    case Int      = 'int';
    case String   = 'string';
    case Text     = 'text';
    case LongText = 'longtext';
    case Decimal  = 'decimal';
    case Bool     = 'bool';
    case Json     = 'json';
    case DateTime = 'datetime';
    case Enum     = 'enum';
}
