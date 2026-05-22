<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Dto\Audience\AudienceTopic;
use Lettr\Enums\AudienceTopicVisibility;
use Traversable;

/**
 * @implements IteratorAggregate<int, AudienceTopic>
 */
final readonly class AudienceTopicCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, AudienceTopic>
     */
    private array $items;

    /**
     * @param  array<int, AudienceTopic>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * @param  array<int, AudienceTopic>  $items
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
     * @return array<int, AudienceTopic>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function findById(string $id): ?AudienceTopic
    {
        foreach ($this->items as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }

        return null;
    }

    public function filterByVisibility(AudienceTopicVisibility $visibility): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (AudienceTopic $topic): bool => $topic->visibility === $visibility,
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
     * @return Traversable<int, AudienceTopic>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, AudienceTopic>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
