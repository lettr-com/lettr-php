<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

use Lettr\Enums\TransmissionState;

/**
 * Detail of a sent or scheduled email transmission.
 *
 * Backs both `GET /emails/{requestId}` and `GET /emails/scheduled/{id}` —
 * per spec, they share an identical response shape.
 */
final readonly class TransmissionDetail
{
    /**
     * @param  array<string>  $recipients
     * @param  array<int, EmailEvent>  $events
     */
    public function __construct(
        public string $transmissionId,
        public TransmissionState $state,
        public ?string $scheduledAt,
        public string $from,
        public ?string $fromName,
        public string $subject,
        public array $recipients,
        public int $numRecipients,
        public array $events,
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        return new self(
            transmissionId: $data['transmission_id'],
            state: TransmissionState::from($data['state']),
            scheduledAt: $data['scheduled_at'] ?? null,
            from: $data['from'],
            fromName: $data['from_name'] ?? null,
            subject: $data['subject'],
            recipients: $data['recipients'],
            numRecipients: $data['num_recipients'],
            events: array_map(
                static fn (array $event): EmailEvent => EmailEvent::from($event),
                $data['events'] ?? []
            ),
        );
    }
}
