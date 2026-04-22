<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

/**
 * Geolocation data derived from the IP address of an open/click event.
 */
final readonly class GeoIp
{
    public function __construct(
        public ?string $country,
        public ?string $region,
        public ?string $city,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $zip,
        public ?string $postalCode,
    ) {}

    /**
     * @param  array{
     *     country?: string|null,
     *     region?: string|null,
     *     city?: string|null,
     *     latitude?: float|int|null,
     *     longitude?: float|int|null,
     *     zip?: string|null,
     *     postal_code?: string|null,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            country: $data['country'] ?? null,
            region: $data['region'] ?? null,
            city: $data['city'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            zip: $data['zip'] ?? null,
            postalCode: $data['postal_code'] ?? null,
        );
    }
}
