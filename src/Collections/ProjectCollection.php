<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Dto\Project\Project;
use Traversable;

/**
 * Collection of projects.
 *
 * @implements IteratorAggregate<int, Project>
 */
final readonly class ProjectCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, Project>
     */
    private array $items;

    /**
     * @param  array<int, Project>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * Create a new collection from an array of projects.
     *
     * @param  array<int, Project>  $items
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
     * Get all projects.
     *
     * @return array<int, Project>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get the first project in the collection.
     */
    public function first(): ?Project
    {
        return $this->items[0] ?? null;
    }

    /**
     * Find a project by ID.
     */
    public function findById(int $id): ?Project
    {
        foreach ($this->items as $project) {
            if ($project->id === $id) {
                return $project;
            }
        }

        return null;
    }

    /**
     * Find a project by name.
     */
    public function findByName(string $name): ?Project
    {
        foreach ($this->items as $project) {
            if ($project->name === $name) {
                return $project;
            }
        }

        return null;
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
     * @return Traversable<int, Project>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, Project>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
