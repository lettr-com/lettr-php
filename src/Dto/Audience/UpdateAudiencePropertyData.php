<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Request body for updating an audience property's fallback value.
 *
 * `name` and `type` cannot be changed (they would orphan data on existing
 * contacts). Only `fallback_value` is mutable.
 *
 * Use `UpdateAudiencePropertyData::clearFallback()` to send an explicit
 * `null` (which removes the existing fallback).
 */
final readonly class UpdateAudiencePropertyData implements Arrayable
{
    private function __construct(
        public ?string $fallbackValue,
        private bool $sendFallback,
    ) {}

    public static function withFallback(string $fallbackValue): self
    {
        return new self($fallbackValue, true);
    }

    public static function clearFallback(): self
    {
        return new self(null, true);
    }

    /**
     * Builds an empty patch (the server returns the property unchanged).
     */
    public static function empty(): self
    {
        return new self(null, false);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if (! $this->sendFallback) {
            return [];
        }

        return [
            'fallback_value' => $this->fallbackValue,
        ];
    }
}
