<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * Subscription status of an audience contact.
 */
enum AudienceContactStatus: string
{
    case Subscribed = 'subscribed';
    case Unsubscribed = 'unsubscribed';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Unverified = 'unverified';

    public function label(): string
    {
        return match ($this) {
            self::Subscribed => 'Subscribed',
            self::Unsubscribed => 'Unsubscribed',
            self::Bounced => 'Bounced',
            self::Complained => 'Complained',
            self::Unverified => 'Unverified',
        };
    }

    /**
     * Whether the contact is in a state that can still receive emails.
     */
    public function canReceiveEmails(): bool
    {
        return $this === self::Subscribed;
    }
}
