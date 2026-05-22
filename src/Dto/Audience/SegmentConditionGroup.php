<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * A group of segment conditions joined by AND.
 *
 * Multiple groups within a segment are joined by OR.
 */
final readonly class SegmentConditionGroup implements Arrayable
{
    /**
     * @param  array<int, SegmentCondition>  $conditions
     */
    public function __construct(
        public array $conditions,
    ) {}

    /**
     * @param  array{conditions: array<int, array{field: string, operator: string, value?: string|null}>}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            conditions: array_map(
                static fn (array $condition): SegmentCondition => SegmentCondition::from($condition),
                $data['conditions'],
            ),
        );
    }

    /**
     * @return array{conditions: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'conditions' => array_map(
                static fn (SegmentCondition $condition): array => $condition->toArray(),
                $this->conditions,
            ),
        ];
    }
}
