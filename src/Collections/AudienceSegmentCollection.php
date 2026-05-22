<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Dto\Audience\AudienceSegment;
use Traversable;

/**
 * @implements IteratorAggregate<int, AudienceSegment>
 */
final readonly class AudienceSegmentCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, AudienceSegment>
     */
    private array $items;

    /**
     * @param  array<int, AudienceSegment>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * @param  array<int, AudienceSegment>  $items
     */
    public static function from(array $items): self
    {
        return new self($items);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @return array<int, AudienceSegment>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function findById(string $id): ?AudienceSegment
    {
        foreach ($this->items as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }

        return null;
    }

    public function filterByList(string $listId): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (AudienceSegment $segment): bool => $segment->listId === $listId,
            ),
        );
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, AudienceSegment>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, AudienceSegment>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
