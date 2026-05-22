<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Dto\Audience\AudienceProperty;
use Lettr\Enums\AudiencePropertyType;
use Traversable;

/**
 * @implements IteratorAggregate<int, AudienceProperty>
 */
final readonly class AudiencePropertyCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, AudienceProperty>
     */
    private array $items;

    /**
     * @param  array<int, AudienceProperty>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * @param  array<int, AudienceProperty>  $items
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
     * @return array<int, AudienceProperty>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function findByName(string $name): ?AudienceProperty
    {
        foreach ($this->items as $item) {
            if ($item->name === $name) {
                return $item;
            }
        }

        return null;
    }

    public function filterByType(AudiencePropertyType $type): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (AudienceProperty $property): bool => $property->type === $type,
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
     * @return Traversable<int, AudienceProperty>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, AudienceProperty>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
