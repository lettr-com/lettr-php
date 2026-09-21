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

    /*
     * The states below are the sending provider's, not Lettr's. Reading back a
     * legacy provider transmission id is answered from delivery events, which
     * report the provider's vocabulary - so these arrive on that path only, and
     * never on a `sch_` id.
     */

    /** @deprecated Legacy provider state. */
    case Submitted = 'submitted';

    /** @deprecated Legacy provider state. */
    case Generating = 'generating';

    /** @deprecated Legacy provider state. */
    case Delivered = 'delivered';

    /** @deprecated Legacy provider state. */
    case Bounced = 'bounced';

    /** A state this version of the SDK does not know. */
    case Unknown = 'unknown';

    /**
     * Resolve a wire value, without throwing on one we do not know.
     *
     * A state the API adds later must not turn every read into a `ValueError`,
     * so an unrecognised value becomes {@see self::Unknown}.
     */
    public static function fromWire(string $value): self
    {
        return self::tryFrom($value) ?? self::Unknown;
    }

    /** Whether the email can still be cancelled. */
    public function isCancellable(): bool
    {
        return $this === self::Scheduled;
    }

    /** Whether the email has reached a final state. */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Sent, self::Cancelled, self::Failed,
            self::Delivered, self::Bounced => true,
            self::Scheduled, self::Sending,
            self::Submitted, self::Generating, self::Unknown => false,
        };
    }
}
