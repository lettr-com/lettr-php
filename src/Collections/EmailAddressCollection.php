<?php

declare(strict_types=1);

namespace Lettr\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Lettr\Exceptions\InvalidValueException;
use Lettr\ValueObjects\EmailAddress;
use Traversable;

/**
 * Collection of email addresses.
 *
 * @implements IteratorAggregate<int, EmailAddress>
 */
final readonly class EmailAddressCollection implements Countable, IteratorAggregate, JsonSerializable
{
    private const MAX_RECIPIENTS = 50;

    /**
     * @var array<int, EmailAddress>
     */
    private array $items;

    /**
     * @param  array<int, EmailAddress>  $items
     */
    private function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * Create a new collection from an array of email addresses.
     *
     * @param  array<int, string|EmailAddress>  $items
     */
    public static function from(array $items): self
    {
        $addresses = [];
        foreach ($items as $item) {
            $addresses[] = $item instanceof EmailAddress ? $item : EmailAddress::from($item);
        }

        return new self($addresses);
    }

    /**
     * Create an empty collection.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Create a collection for recipients (validates min/max).
     *
     * @param  array<int, string|EmailAddress>  $items
     */
    public static function forRecipients(array $items): self
    {
        if (count($items) === 0) {
            throw new InvalidValueException('At least one recipient is required.');
        }

        if (count($items) > self::MAX_RECIPIENTS) {
            throw new InvalidValueException(
                sprintf('Maximum %d recipients allowed, %d provided.', self::MAX_RECIPIENTS, count($items))
            );
        }

        return self::from($items);
    }

    /**
     * Add an email address to the collection.
     */
    public function add(string|EmailAddress $address): self
    {
        $email = $address instanceof EmailAddress ? $address : EmailAddress::from($address);

        return new self([...$this->items, $email]);
    }

    /**
     * Get all email addresses.
     *
     * @return array<int, EmailAddress>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get the first email address.
     */
    public function first(): ?EmailAddress
    {
        return $this->items[0] ?? null;
    }

    /**
     * Check if the collection contains an email address.
     */
    public function contains(string|EmailAddress $address): bool
    {
        $email = $address instanceof EmailAddress ? $address : EmailAddress::from($address);

        foreach ($this->items as $item) {
            if ($item->equals($email)) {
                return true;
            }
        }

        return false;
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
            static fn (EmailAddress $address): string => $address->address,
            $this->items
        );
    }

    /**
     * Convert to an array of formatted strings (with names).
     *
     * @return array<int, string>
     */
    public function toFormattedStrings(): array
    {
        return array_map(
            static fn (EmailAddress $address): string => $address->formatted(),
            $this->items
        );
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, EmailAddress>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, EmailAddress>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
