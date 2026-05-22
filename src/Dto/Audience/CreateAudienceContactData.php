<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Request body for creating a single audience contact.
 */
final readonly class CreateAudienceContactData implements Arrayable
{
    /**
     * @param  array<string, string>|null  $properties
     */
    public function __construct(
        public string $email,
        public ?string $listId = null,
        public ?array $properties = null,
        public ?DoubleOptInConfig $doubleOptIn = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'email' => $this->email,
        ];

        if ($this->listId !== null) {
            $payload['list_id'] = $this->listId;
        }

        if ($this->properties !== null) {
            $payload['properties'] = $this->properties;
        }

        if ($this->doubleOptIn !== null) {
            $payload['double_opt_in'] = $this->doubleOptIn->toArray();
        }

        return $payload;
    }
}
