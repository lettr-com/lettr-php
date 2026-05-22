<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Enums\EventType;
use Traversable;

/**
 * Collection of event types for webhook configuration.
 *
 * @implements IteratorAggregate<int, EventType>
 */
final readonly class EventTypeCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, EventType>
     */
    private array $items;

    /**
     * @param  array<int, EventType>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values(array_unique($items, SORT_REGULAR));
    }

    /**
     * Create a new collection from an array of event types.
     *
     * @param  array<int, EventType|string>  $items
     */
    public static function from(array $items): self
    {
        $types = [];
        foreach ($items as $item) {
            $types[] = $item instanceof EventType ? $item : EventType::from($item);
        }

        return new self($types);
    }

    /**
     * Create an empty collection.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Create a collection with all event types.
     */
    public static function allTypes(): self
    {
        return new self(EventType::cases());
    }

    /**
     * Create a collection with delivery-related event types.
     */
    public static function deliveryEvents(): self
    {
        return new self([
            EventType::Injection,
            EventType::Delivery,
            EventType::Bounce,
            EventType::Delay,
            EventType::PolicyRejection,
            EventType::OutOfBand,
            EventType::GenerationFailure,
            EventType::GenerationRejection,
        ]);
    }

    /**
     * Create a collection with engagement event types.
     */
    public static function engagementEvents(): self
    {
        return new self([
            EventType::Open,
            EventType::InitialOpen,
            EventType::Click,
        ]);
    }

    /**
     * Add an event type to the collection.
     */
    public function add(EventType $type): self
    {
        return new self([...$this->items, $type]);
    }

    /**
     * Get all event types.
     *
     * @return array<int, EventType>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Check if the collection contains an event type.
     */
    public function contains(EventType $type): bool
    {
        return in_array($type, $this->items, true);
    }

    /**
     * Check if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    /**
     * Convert to an array of strings.
     *
     * @return array<int, string>
     */
    public function toStrings(): array
    {
        return array_map(
            static fn (EventType $type): string => $type->value,
            $this->items
        );
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, EventType>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, EventType>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
