<?php

declare(strict_types=1);

namespace Lettr\Responses;

use Lettr\Collections\AudienceListCollection;
use Lettr\Dto\Audience\AudienceList;

/**
 * Response from listing audience lists.
 */
final readonly class ListAudienceListsResponse
{
    public function __construct(
        public AudienceListCollection $lists,
        public AudiencePagination $pagination,
    ) {}

    /**
     * @param  array{
     *     lists: array<int, array{id: string, name: string, contacts_count: int}>,
     *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            lists: AudienceListCollection::from(
                array_map(
                    static fn (array $list): AudienceList => AudienceList::from($list),
                    $data['lists'],
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
