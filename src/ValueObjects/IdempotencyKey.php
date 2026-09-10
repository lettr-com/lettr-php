<?php

declare(strict_types=1);

namespace Lettr\ValueObjects;

use Lettr\Contracts\Arrayable;
use Lettr\Exceptions\InvalidValueException;
use Stringable;

/**
 * A key identifying one logical send.
 *
 * Validated here rather than at the API, so a malformed key fails on your
 * machine instead of costing a round trip and a 422.
 *
 * The point of the key is to be **the same** across two attempts at one send.
 * The SDK never retries - one `send()` is one HTTP request - so the retry is
 * yours, and only you know that two calls are the same logical send. That is
 * why nothing here generates a key for you by default: a key minted inside
 * `send()` would differ on every attempt and protect nothing.
 */
final readonly class IdempotencyKey implements Arrayable, Stringable
{
    private const PATTERN = '/\A[A-Za-z0-9._-]{1,255}\z/';

    public function __construct(public string $value)
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidValueException(
                'An idempotency key must be 1 to 255 characters of letters, digits, periods, underscores or hyphens.'
            );
        }
    }

    /**
     * A key derived from the payload itself, for callers with no natural id.
     *
     * Deterministic: the same email produces the same key, so a retry
     * deduplicates without you tracking anything. The trade is that two
     * *deliberately* identical sends within the provider's 24 hour window
     * collapse into one - the second returns a replay and no email goes out.
     * Opt into this only where that is what you want.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function forPayload(array $payload): self
    {
        return new self(hash('sha256', (string) json_encode($payload)));
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return ['Idempotency-Key' => $this->value];
    }
}
