<?php

declare(strict_types=1);

namespace Lettr\Enums;

/**
 * Lifecycle of a scheduled email.
 *
 * Lettr holds a scheduled email until it is due, then hands it to the sending
 * provider, so these states are Lettr's own. They are not the transmission
 * states in {@see TransmissionState}, which still describe a sent email
 * (`GET /emails/{requestId}`).
 */
enum ScheduledEmailState: string
{
    /** Waiting for its delivery time. The only state that can be cancelled. */
    case Scheduled = 'scheduled';

    /** Being handed to the sending provider right now. */
    case Sending = 'sending';

    /** Sent. Delivery detail then comes from the email's events. */
    case Sent = 'sent';

    /** Cancelled before it was sent. */
    case Cancelled = 'cancelled';

    /** Sending was attempted and gave up. See `failureReason`. */
    case Failed = 'failed';

    /** Whether the email can still be cancelled. */
    public function isCancellable(): bool
    {
        return $this === self::Scheduled;
    }

    /** Whether the email has reached a final state. */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Sent, self::Cancelled, self::Failed => true,
            self::Scheduled, self::Sending => false,
        };
    }
}
