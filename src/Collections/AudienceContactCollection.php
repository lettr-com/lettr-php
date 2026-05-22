<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Dto\Audience\AudienceContact;
use Lettr\Enums\AudienceContactStatus;
use Traversable;

/**
 * @implements IteratorAggregate<int, AudienceContact>
 */
final readonly class AudienceContactCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, AudienceContact>
     */
    private array $items;

    /**
     * @param  array<int, AudienceContact>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * @param  array<int, AudienceContact>  $items
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
     * @return array<int, AudienceContact>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function findByEmail(string $email): ?AudienceContact
    {
        foreach ($this->items as $item) {
            if ($item->email === $email) {
                return $item;
            }
        }

        return null;
    }

    public function filterByStatus(AudienceContactStatus $status): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (AudienceContact $contact): bool => $contact->status === $status,
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
     * @return Traversable<int, AudienceContact>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, AudienceContact>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
