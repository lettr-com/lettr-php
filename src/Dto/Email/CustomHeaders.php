<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

use Lettr\Contracts\Arrayable;
use Lettr\Exceptions\InvalidValueException;

/**
 * Custom email headers (key-value pairs).
 */
final readonly class CustomHeaders implements Arrayable
{
    private const MAX_HEADERS = 10;

    private const MAX_VALUE_LENGTH = 998;

    /**
     * @var array<string, string>
     */
    private array $data;

    /**
     * @param  array<string, string>  $data
     */
    public function __construct(array $data = [])
    {
        self::validate($data);
        $this->data = $data;
    }

    /**
     * Create from an array.
     *
     * @param  array<string, string>  $data
     */
    public static function from(array $data): self
    {
        return new self($data);
    }

    /**
     * Create empty headers.
     */
    public static function empty(): self
    {
        return new self;
    }

    /**
     * Add a header.
     */
    public function set(string $key, string $value): self
    {
        return new self([...$this->data, $key => $value]);
    }

    /**
     * Get a header value by name.
     */
    public function get(string $key, ?string $default = null): ?string
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if a header exists.
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get all headers.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Check if headers are empty.
     */
    public function isEmpty(): bool
    {
        return count($this->data) === 0;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param  array<string, string>  $data
     */
    private static function validate(array $data): void
    {
        if (count($data) > self::MAX_HEADERS) {
            throw new InvalidValueException('Custom headers cannot exceed '.self::MAX_HEADERS.' entries.');
        }

        foreach ($data as $value) {
            if (strlen($value) > self::MAX_VALUE_LENGTH) {
                throw new InvalidValueException('Custom header value cannot exceed '.self::MAX_VALUE_LENGTH.' characters.');
            }
        }
    }
}
