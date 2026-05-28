<?php

declare(strict_types=1);

namespace Lettr\Responses;

use Lettr\Dto\Campaign\CampaignEvent;

/**
 * Response from listing campaign engagement events (cursor-paginated).
 *
 * @phpstan-import-type CampaignEventData from CampaignEvent
 */
final readonly class ListCampaignEventsResponse
{
    /**
     * @param  array<int, CampaignEvent>  $events
     */
    public function __construct(
        public array $events,
        public ?string $nextCursor,
    ) {}

    /**
     * @param  array{
     *     events: array<int, CampaignEventData>,
     *     next_cursor?: string|null,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            events: array_map(
                static fn (array $event): CampaignEvent => CampaignEvent::from($event),
                $data['events'],
            ),
            nextCursor: $data['next_cursor'] ?? null,
        );
    }

    /**
     * Check if there are more events to fetch.
     */
    public function hasMore(): bool
    {
        return $this->nextCursor !== null;
    }
}
