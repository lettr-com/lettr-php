<?php

declare(strict_types=1);

namespace Lettr\ValueObjects;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Custom property values stored on an audience contact.
 *
 * The Lettr API returns every property value as a string, regardless of the
 * property's declared type. Callers needing typed access should cast
 * downstream using the matching AudienceProperty's type.
 *
 * @implements IteratorAggregate<string, string>
 */
final readonly class ContactProperties implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array<string, string>
     */
    private array $values;

    /**
     * @param  array<string, string>  $values
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    /**
     * @param  array<string, string>  $values
     */
    public static function from(array $values): self
    {
        return new self($values);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function get(string $name): ?string
    {
        return $this->values[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->values);
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->values;
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    public function count(): int
    {
        return count($this->values);
    }

    /**
     * @return Traversable<string, string>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    /**
     * Serializes as a JSON object preserving property keys.
     *
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return $this->values;
    }
}
