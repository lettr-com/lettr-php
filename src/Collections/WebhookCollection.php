<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Dto\Webhook\Webhook;
use Traversable;

/**
 * Collection of webhooks.
 *
 * @implements IteratorAggregate<int, Webhook>
 */
final readonly class WebhookCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, Webhook>
     */
    private array $items;

    /**
     * @param  array<int, Webhook>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * Create a new collection from an array of webhooks.
     *
     * @param  array<int, Webhook>  $items
     */
    public static function from(array $items): self
    {
        return new self($items);
    }

    /**
     * Create an empty collection.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Get all webhooks.
     *
     * @return array<int, Webhook>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get enabled webhooks.
     */
    public function enabled(): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (Webhook $webhook): bool => $webhook->enabled
            )
        );
    }

    /**
     * Get disabled webhooks.
     */
    public function disabled(): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (Webhook $webhook): bool => ! $webhook->enabled
            )
        );
    }

    /**
     * Check if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, Webhook>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, Webhook>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
