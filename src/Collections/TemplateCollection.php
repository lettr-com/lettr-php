<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Dto\Template\Template;
use Traversable;

/**
 * Collection of templates.
 *
 * @implements IteratorAggregate<int, Template>
 */
final readonly class TemplateCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<int, Template>
     */
    private array $items;

    /**
     * @param  array<int, Template>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * Create a new collection from an array of templates.
     *
     * @param  array<int, Template>  $items
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
     * Get all templates.
     *
     * @return array<int, Template>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get the first template in the collection.
     */
    public function first(): ?Template
    {
        return $this->items[0] ?? null;
    }

    /**
     * Filter templates by project ID.
     */
    public function filterByProject(int $projectId): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (Template $template): bool => $template->projectId === $projectId
            )
        );
    }

    /**
     * Filter templates by folder ID.
     */
    public function filterByFolder(?int $folderId): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (Template $template): bool => $template->folderId === $folderId
            )
        );
    }

    /**
     * Find a template by slug.
     */
    public function findBySlug(string $slug): ?Template
    {
        foreach ($this->items as $template) {
            if ($template->slug === $slug) {
                return $template;
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
     * @return Traversable<int, Template>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, Template>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
