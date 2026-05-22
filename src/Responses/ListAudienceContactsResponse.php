<?php

declare(strict_types=1);

namespace Lettr\Responses;

use Lettr\Collections\AudienceContactCollection;
use Lettr\Dto\Audience\AudienceContact;

/**
 * Response from listing audience contacts.
 */
final readonly class ListAudienceContactsResponse
{
    public function __construct(
        public AudienceContactCollection $contacts,
        public AudiencePagination $pagination,
    ) {}

    /**
     * @param  array{
     *     contacts: array<int, array{
     *         id: string,
     *         email: string,
     *         status: string,
     *         properties: array<string, string>,
     *         created_at: string,
     *         lists: array<int, array{id: string, name: string}>,
     *         topics: array<int, array{id: string, name: string}>,
     *     }>,
     *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            contacts: AudienceContactCollection::from(
                array_map(
                    static fn (array $contact): AudienceContact => AudienceContact::from($contact),
                    $data['contacts'],
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
