<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * Default subscription behavior for an audience topic.
 *
 * - `OptIn`  — new contacts are NOT subscribed by default; they must opt in to receive messages.
 * - `OptOut` — new contacts ARE subscribed by default; they can opt out to stop receiving messages.
 */
enum AudienceTopicDefaultSubscription: string
{
    case OptIn = 'opt_in';
    case OptOut = 'opt_out';

    public function label(): string
    {
        return match ($this) {
            self::OptIn => 'Opt-in',
            self::OptOut => 'Opt-out',
        };
    }

    public function isOptIn(): bool
    {
        return $this === self::OptIn;
    }

    /**
     * Whether new contacts on this topic are subscribed by default.
     *
     * Under `OptOut` semantics, contacts are auto-subscribed and must opt out
     * to stop receiving messages. Under `OptIn`, they start unsubscribed.
     */
    public function subscribesNewContactsByDefault(): bool
    {
        return $this === self::OptOut;
    }
}
