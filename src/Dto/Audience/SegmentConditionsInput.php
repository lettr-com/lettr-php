<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Request-side wrapper for the `conditions` field on segment create/update.
 *
 * The request shape is `{ groups: [...] }` whereas the response entity exposes
 * `condition_groups` as a top-level array — see {@see AudienceSegment::$conditionGroups}.
 */
final readonly class SegmentConditionsInput implements Arrayable
{
    /**
     * @param  array<int, SegmentConditionGroup>  $groups
     */
    public function __construct(
        public array $groups,
    ) {}

    /**
     * @return array{groups: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'groups' => array_map(
                static fn (SegmentConditionGroup $group): array => $group->toArray(),
                $this->groups,
            ),
        ];
    }
}
