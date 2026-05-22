<?php

declare(strict_types=1);

namespace Lettr\Responses;

use Lettr\Collections\AudienceTopicCollection;
use Lettr\Dto\Audience\AudienceTopic;

/**
 * Response from listing audience topics.
 */
final readonly class ListAudienceTopicsResponse
{
    public function __construct(
        public AudienceTopicCollection $topics,
        public AudiencePagination $pagination,
    ) {}

    /**
     * @param  array{
     *     topics: array<int, array{
     *         id: string,
     *         name: string,
     *         description: string|null,
     *         default_subscription: string,
     *         visibility: string,
     *         contacts_count: int,
     *         created_at: string|null,
     *     }>,
     *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            topics: AudienceTopicCollection::from(
                array_map(
                    static fn (array $topic): AudienceTopic => AudienceTopic::from($topic),
                    $data['topics'],
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
