<?php

declare(strict_types=1);

namespace Lettr\Dto\Campaign;

use Lettr\Enums\EventType;

/**
 * A single campaign engagement event.
 *
 * `$eventType` is an {@see EventType} for spec-known values, or the raw
 * string for any value the SDK doesn't yet recognise. The campaigns endpoint
 * only emits the seven engagement subset values (`injection`, `delivery`,
 * `bounce`, `spam_complaint`, `open`, `click`, `list_unsubscribe`), but
 * tolerating unknowns means a server-side spec extension can't crash
 * pagination through history.
 *
 * @phpstan-type CampaignEventData array{
 *     event_id: string,
 *     event_type: string,
 *     email: string,
 *     timestamp: string,
 *     bounce_class?: string|null,
 *     reason?: string|null,
 *     target_link_url?: string|null,
 *     user_agent?: string|null,
 * }
 */
final readonly class CampaignEvent
{
    public function __construct(
        public string $eventId,
        public EventType|string $eventType,
        public string $email,
        public string $timestamp,
        public ?string $bounceClass,
        public ?string $reason,
        public ?string $targetLinkUrl,
        public ?string $userAgent,
    ) {}

    /**
     * @param  CampaignEventData  $data
     */
    public static function from(array $data): self
    {
        return new self(
            eventId: $data['event_id'],
            eventType: EventType::tryFrom($data['event_type']) ?? $data['event_type'],
            email: $data['email'],
            timestamp: $data['timestamp'],
            bounceClass: $data['bounce_class'] ?? null,
            reason: $data['reason'] ?? null,
            targetLinkUrl: $data['target_link_url'] ?? null,
            userAgent: $data['user_agent'] ?? null,
        );
    }
}
