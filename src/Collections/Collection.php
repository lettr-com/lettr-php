<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Abstract base for typed item collections.
 *
 * Holds the shared boilerplate (immutable item array, count/iteration, JSON
 * serialization, empty-check). Subclasses pin the item type via the
 * `@template`-style PHPDoc on their factory methods and may add type-specific
 * finders (e.g. `findById`).
 *
 * @template T of object
 *
 * @implements IteratorAggregate<int, T>
 */
abstract readonly class Collection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, T>
     */
    protected array $items;

    /**
     * @param  array<int, T>  $items
     */
    final protected function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * @param  array<int, T>  $items
     */
    public static function from(array $items): static
    {
        return new static($items);
    }

    public static function empty(): static
    {
        return new static([]);
    }

    /**
     * @return array<int, T>
     */
    public function all(): array
    {
        return $this->items;
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
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Serializes as a JSON array preserving item order.
     *
     * @return array<int, T>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
