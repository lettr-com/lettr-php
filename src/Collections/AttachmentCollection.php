<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Dto\Email\Attachment;
use Traversable;

/**
 * Collection of email attachments.
 *
 * @implements IteratorAggregate<int, Attachment>
 */
final readonly class AttachmentCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, Attachment>
     */
    private array $items;

    /**
     * @param  array<int, Attachment>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * Create a new collection from an array of attachments.
     *
     * @param  array<int, Attachment>  $items
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
     * Add an attachment to the collection.
     */
    public function add(Attachment $attachment): self
    {
        return new self([...$this->items, $attachment]);
    }

    /**
     * Get all attachments.
     *
     * @return array<int, Attachment>
     */
    public function all(): array
    {
        return $this->items;
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
     * @return Traversable<int, Attachment>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, array{name: string, type: string, data: string}>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (Attachment $attachment): array => $attachment->toArray(),
            $this->items
        );
    }

    /**
     * @return array<int, array{name: string, type: string, data: string}>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
