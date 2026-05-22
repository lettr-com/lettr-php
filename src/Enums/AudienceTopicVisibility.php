<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * Visibility of an audience topic.
 */
enum AudienceTopicVisibility: string
{
    case PrivateVisibility = 'private';
    case PublicVisibility = 'public';

    public function label(): string
    {
        return match ($this) {
            self::PrivateVisibility => 'Private',
            self::PublicVisibility => 'Public',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::PublicVisibility;
    }
}
