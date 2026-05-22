<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;
use Lettr\Enums\AudiencePropertyType;

/**
 * Request body for creating an audience property.
 *
 * `name` and `type` are immutable after creation.
 */
final readonly class CreateAudiencePropertyData implements Arrayable
{
    public function __construct(
        public string $name,
        public AudiencePropertyType $type,
        public ?string $fallbackValue = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'name' => $this->name,
            'type' => $this->type->value,
        ];

        if ($this->fallbackValue !== null) {
            $payload['fallback_value'] = $this->fallbackValue;
        }

        return $payload;
    }
}
