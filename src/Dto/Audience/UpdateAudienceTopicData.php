<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;
use Lettr\Enums\AudienceTopicVisibility;

/**
 * Request body for partially updating an audience topic (PATCH).
 *
 * `default_subscription` is immutable and cannot be updated here.
 */
final readonly class UpdateAudienceTopicData implements Arrayable
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?AudienceTopicVisibility $visibility = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [];

        if ($this->name !== null) {
            $payload['name'] = $this->name;
        }

        if ($this->description !== null) {
            $payload['description'] = $this->description;
        }

        if ($this->visibility !== null) {
            $payload['visibility'] = $this->visibility->value;
        }

        return $payload;
    }
}
