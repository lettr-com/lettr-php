<?php

declare(strict_types=1);

namespace Lettr\Responses;

use Lettr\Dto\Email\CursorPagination;
use Lettr\Dto\Email\EmailEvent;

/**
 * Response from listing email events.
 */
final readonly class ListEmailEventsResponse
{
    /**
     * @param  array<int, EmailEvent>  $events
     */
    public function __construct(
        public array $events,
        public int $totalCount,
        public string $from,
        public string $to,
        public CursorPagination $pagination,
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array{
     *     events: array{
     *         data: array<int, array<string, mixed>>,
     *         total_count: int,
     *         from: string,
     *         to: string,
     *         pagination: array{next_cursor: string|null, per_page: int},
     *     },
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            events: array_map(
                static fn (array $event): EmailEvent => EmailEvent::from($event),
                $data['events']['data']
            ),
            totalCount: $data['events']['total_count'],
            from: $data['events']['from'],
            to: $data['events']['to'],
            pagination: CursorPagination::from($data['events']['pagination']),
        );
    }

    /**
     * Check if there are more pages.
     */
    public function hasMore(): bool
    {
        return $this->pagination->hasNextPage();
    }
}
