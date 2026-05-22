<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\ValueObjects\Timestamp;

/**
 * Audience segment entity.
 */
final readonly class AudienceSegment
{
    /**
     * @param  array<int, SegmentConditionGroup>  $conditionGroups
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $listId,
        public ?string $listName,
        public array $conditionGroups,
        public ?int $cachedContactsCount,
        public Timestamp $createdAt,
    ) {}

    /**
     * @param  array{
     *     id: string,
     *     name: string,
     *     list_id: string|null,
     *     list_name: string|null,
     *     condition_groups: array<int, array{conditions: array<int, array{field: string, operator: string, value?: string|null}>}>,
     *     cached_contacts_count: int|null,
     *     created_at: string,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            listId: $data['list_id'],
            listName: $data['list_name'],
            conditionGroups: array_map(
                static fn (array $group): SegmentConditionGroup => SegmentConditionGroup::from($group),
                $data['condition_groups'],
            ),
            cachedContactsCount: $data['cached_contacts_count'],
            createdAt: Timestamp::fromString($data['created_at']),
        );
    }
}
