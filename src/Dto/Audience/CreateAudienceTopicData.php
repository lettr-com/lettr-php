<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;
use Lettr\Enums\AudienceTopicDefaultSubscription;
use Lettr\Enums\AudienceTopicVisibility;

/**
 * Request body for creating an audience topic.
 *
 * `defaultSubscription` is set at creation and cannot be changed later.
 */
final readonly class CreateAudienceTopicData implements Arrayable
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?AudienceTopicDefaultSubscription $defaultSubscription = null,
        public ?AudienceTopicVisibility $visibility = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'name' => $this->name,
        ];

        if ($this->description !== null) {
            $payload['description'] = $this->description;
        }

        if ($this->defaultSubscription !== null) {
            $payload['default_subscription'] = $this->defaultSubscription->value;
        }

        if ($this->visibility !== null) {
            $payload['visibility'] = $this->visibility->value;
        }

        return $payload;
    }
}
