<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * Comparison operator used inside a segment condition.
 */
enum SegmentOperator: string
{
    case Contains = 'contains';
    case NotContains = 'not_contains';
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case StartsWith = 'starts_with';
    case NotStartsWith = 'not_starts_with';
    case EndsWith = 'ends_with';
    case NotEndsWith = 'not_ends_with';
    case IsTrue = 'is_true';
    case IsFalse = 'is_false';
    case GreaterThan = 'greater_than';
    case GreaterThanOrEqual = 'greater_than_or_equal';
    case LessThan = 'less_than';
    case LessThanOrEqual = 'less_than_or_equal';
    case Before = 'before';
    case After = 'after';

    public function label(): string
    {
        return match ($this) {
            self::Contains => 'Contains',
            self::NotContains => 'Does not contain',
            self::Equals => 'Equals',
            self::NotEquals => 'Does not equal',
            self::StartsWith => 'Starts with',
            self::NotStartsWith => 'Does not start with',
            self::EndsWith => 'Ends with',
            self::NotEndsWith => 'Does not end with',
            self::IsTrue => 'Is true',
            self::IsFalse => 'Is false',
            self::GreaterThan => 'Greater than',
            self::GreaterThanOrEqual => 'Greater than or equal',
            self::LessThan => 'Less than',
            self::LessThanOrEqual => 'Less than or equal',
            self::Before => 'Before',
            self::After => 'After',
        };
    }

    /**
     * Whether this operator requires a value to compare against.
     *
     * The `is_true` and `is_false` operators evaluate the field's truthiness
     * and accept no value.
     */
    public function requiresValue(): bool
    {
        return $this !== self::IsTrue && $this !== self::IsFalse;
    }
}
