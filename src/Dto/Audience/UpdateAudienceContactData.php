<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;
use Lettr\Enums\AudienceContactStatus;

/**
 * Request body for partially updating an audience contact (PATCH).
 *
 * For `properties`, set a key to `null` to remove that property from the contact.
 * Only `subscribed` and `unsubscribed` are valid manual status transitions.
 */
final readonly class UpdateAudienceContactData implements Arrayable
{
    /**
     * @param  array<string, string|null>|null  $properties
     */
    public function __construct(
        public ?string $email = null,
        public ?AudienceContactStatus $status = null,
        public ?array $properties = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [];

        if ($this->email !== null) {
            $payload['email'] = $this->email;
        }

        if ($this->status !== null) {
            $payload['status'] = $this->status->value;
        }

        if ($this->properties !== null) {
            $payload['properties'] = $this->properties;
        }

        return $payload;
    }
}
