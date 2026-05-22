<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Enums\WebhookEventType;
use Traversable;

/**
 * Collection of webhook event types (namespaced values like `message.delivery`).
 *
 * @implements IteratorAggregate<int, WebhookEventType>
 */
final readonly class WebhookEventTypeCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, WebhookEventType>
     */
    private array $items;

    /**
     * @param  array<int, WebhookEventType>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values(array_unique($items, SORT_REGULAR));
    }

    /**
     * @param  array<int, WebhookEventType|string>  $items
     */
    public static function from(array $items): self
    {
        $types = [];
        foreach ($items as $item) {
            $types[] = $item instanceof WebhookEventType ? $item : WebhookEventType::from($item);
        }

        return new self($types);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function allTypes(): self
    {
        return new self(WebhookEventType::cases());
    }

    public function add(WebhookEventType $type): self
    {
        return new self([...$this->items, $type]);
    }

    /**
     * @return array<int, WebhookEventType>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function contains(WebhookEventType $type): bool
    {
        return in_array($type, $this->items, true);
    }

    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    /**
     * @return array<int, string>
     */
    public function toStrings(): array
    {
        return array_map(
            static fn (WebhookEventType $type): string => $type->value,
            $this->items,
        );
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, WebhookEventType>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, WebhookEventType>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
