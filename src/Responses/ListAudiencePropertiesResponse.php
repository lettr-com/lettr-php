<?php

declare(strict_types=1);

namespace Lettr\Responses;

use Lettr\Collections\AudiencePropertyCollection;
use Lettr\Dto\Audience\AudienceProperty;

/**
 * Response from listing audience properties.
 */
final readonly class ListAudiencePropertiesResponse
{
    public function __construct(
        public AudiencePropertyCollection $properties,
        public AudiencePagination $pagination,
    ) {}

    /**
     * @param  array{
     *     properties: array<int, array{
     *         id: string,
     *         name: string,
     *         type: string,
     *         fallback_value: string|null,
     *         created_at: string,
     *     }>,
     *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            properties: AudiencePropertyCollection::from(
                array_map(
                    static fn (array $property): AudienceProperty => AudienceProperty::from($property),
                    $data['properties'],
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
