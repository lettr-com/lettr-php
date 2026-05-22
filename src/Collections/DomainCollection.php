<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Dto\Domain\Domain;
use Lettr\Enums\DomainStatus;
use Traversable;

/**
 * Collection of domains.
 *
 * @implements IteratorAggregate<int, Domain>
 */
final readonly class DomainCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, Domain>
     */
    private array $items;

    /**
     * @param  array<int, Domain>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * Create a new collection from an array of domains.
     *
     * @param  array<int, Domain>  $items
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
     * Get all domains.
     *
     * @return array<int, Domain>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Filter domains by status.
     */
    public function filterByStatus(DomainStatus $status): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (Domain $domain): bool => $domain->status === $status
            )
        );
    }

    /**
     * Get all verified/approved domains.
     */
    public function verified(): self
    {
        return $this->filterByStatus(DomainStatus::Approved);
    }

    /**
     * Get all pending domains.
     */
    public function pending(): self
    {
        return $this->filterByStatus(DomainStatus::Pending);
    }

    /**
     * Get domains that can send emails.
     */
    public function canSend(): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (Domain $domain): bool => $domain->canSend
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
     * @return Traversable<int, Domain>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, Domain>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
