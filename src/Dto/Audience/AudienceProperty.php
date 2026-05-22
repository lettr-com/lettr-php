<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Enums\AudiencePropertyType;
use Lettr\ValueObjects\Timestamp;

/**
 * Audience property entity.
 */
final readonly class AudienceProperty
{
    public function __construct(
        public string $id,
        public string $name,
        public AudiencePropertyType $type,
        public ?string $fallbackValue,
        public Timestamp $createdAt,
    ) {}

    /**
     * @param  array{
     *     id: string,
     *     name: string,
     *     type: string,
     *     fallback_value: string|null,
     *     created_at: string,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            type: AudiencePropertyType::from($data['type']),
            fallbackValue: $data['fallback_value'],
            createdAt: Timestamp::fromString($data['created_at']),
        );
    }
}
