<?php

declare(strict_types=1);

namespace Lettr\Responses;

use Lettr\Collections\AudienceSegmentCollection;
use Lettr\Dto\Audience\AudienceSegment;

/**
 * Response from listing audience segments.
 */
final readonly class ListAudienceSegmentsResponse
{
    public function __construct(
        public AudienceSegmentCollection $segments,
        public AudiencePagination $pagination,
    ) {}

    /**
     * @param  array{
     *     segments: array<int, array{
     *         id: string,
     *         name: string,
     *         list_id: string|null,
     *         list_name: string|null,
     *         condition_groups: array<int, array{conditions: array<int, array{field: string, operator: string, value?: string|null}>}>,
     *         cached_contacts_count: int|null,
     *         created_at: string,
     *     }>,
     *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            segments: AudienceSegmentCollection::from(
                array_map(
                    static fn (array $segment): AudienceSegment => AudienceSegment::from($segment),
                    $data['segments'],
                ),
            ),
            pagination: AudiencePagination::from($data['pagination']),
        );
    }

    public function hasMore(): bool
    {
        return $this->pagination->hasNextPage();
    }
}
