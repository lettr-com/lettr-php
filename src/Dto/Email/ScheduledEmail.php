<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

use Lettr\Enums\ScheduledEmailState;
use Lettr\ValueObjects\RequestId;

/**
 * An email scheduled for later delivery.
 *
 * Returned by `schedule()`, `getScheduled()`, `cancelScheduled()` and
 * `listScheduled()`.
 *
 * Two ids, and they are not interchangeable:
 *
 * - `requestId` (`sch_...`) identifies the scheduled email for its whole life
 *   and is what `getScheduled()` and `cancelScheduled()` take.
 * - `transmissionId` is the sending provider's id. It is `null` until the
 *   email is actually sent, and it is the value that appears on webhook
 *   events, so use it to correlate them.
 */
final readonly class ScheduledEmail
{
    /**
     * @param  array<string>  $recipients
     * @param  array<int, EmailEvent>  $events
     */
    public function __construct(
        public RequestId $requestId,
        public ?string $transmissionId,
        public ScheduledEmailState $state,
        public string $scheduledAt,
        public string $from,
        public ?string $fromName,
        public ?string $subject,
        public array $recipients,
        public int $numRecipients,
        public int $accepted,
        public int $rejected,
        public ?string $tag = null,
        public ?string $failureReason = null,
        public array $events = [],
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        /** @var array<int, array<string, mixed>> $events */
        $events = $data['events'] ?? [];

        /** @var array<string> $recipients */
        $recipients = $data['recipients'] ?? [];

        return new self(
            requestId: new RequestId((string) $data['request_id']),
            transmissionId: isset($data['transmission_id']) ? (string) $data['transmission_id'] : null,
            state: ScheduledEmailState::from((string) $data['state']),
            scheduledAt: (string) $data['scheduled_at'],
            from: (string) $data['from'],
            fromName: isset($data['from_name']) ? (string) $data['from_name'] : null,
            subject: isset($data['subject']) ? (string) $data['subject'] : null,
            recipients: $recipients,
            numRecipients: (int) ($data['num_recipients'] ?? count($recipients)),
            accepted: (int) ($data['accepted'] ?? 0),
            rejected: (int) ($data['rejected'] ?? 0),
            tag: isset($data['tag']) ? (string) $data['tag'] : null,
            failureReason: isset($data['failure_reason']) ? (string) $data['failure_reason'] : null,
            events: array_map(
                static fn (array $event): EmailEvent => EmailEvent::from($event),
                $events
            ),
        );
    }

    /**
     * Whether the email can still be cancelled.
     */
    public function isCancellable(): bool
    {
        return $this->state->isCancellable();
    }

    /**
     * Whether the email has been handed to the sending provider.
     */
    public function isSent(): bool
    {
        return $this->state === ScheduledEmailState::Sent;
    }

    /**
     * Whether the email was cancelled before it was sent.
     */
    public function isCancelled(): bool
    {
        return $this->state === ScheduledEmailState::Cancelled;
    }
}
