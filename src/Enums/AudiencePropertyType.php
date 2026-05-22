<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * Data type of an audience property.
 */
enum AudiencePropertyType: string
{
    case StringType = 'string';
    case NumberType = 'number';
    case BooleanType = 'boolean';
    case DateType = 'date';
    case JsonType = 'json';

    public function label(): string
    {
        return match ($this) {
            self::StringType => 'String',
            self::NumberType => 'Number',
            self::BooleanType => 'Boolean',
            self::DateType => 'Date',
            self::JsonType => 'JSON',
        };
    }
}
