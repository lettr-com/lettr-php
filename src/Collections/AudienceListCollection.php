<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Dto\Audience\AudienceList;
use Traversable;

/**
 * @implements IteratorAggregate<int, AudienceList>
 */
final readonly class AudienceListCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, AudienceList>
     */
    private array $items;

    /**
     * @param  array<int, AudienceList>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * @param  array<int, AudienceList>  $items
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
     * @return array<int, AudienceList>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function findById(string $id): ?AudienceList
    {
        foreach ($this->items as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }

        return null;
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
     * @return Traversable<int, AudienceList>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Serializes as a JSON array preserving item order.
     *
     * @return array<int, AudienceList>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
