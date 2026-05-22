<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Request body for bulk creating up to 1000 audience contacts.
 *
 * All contacts share the same optional list and properties. Existing emails
 * are reported in the response's `already_existed` count.
 */
final readonly class BulkCreateAudienceContactsData implements Arrayable
{
    /**
     * @param  array<int, string>  $emails
     * @param  array<string, string>|null  $properties
     */
    public function __construct(
        public array $emails,
        public ?string $listId = null,
        public ?array $properties = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'emails' => array_values($this->emails),
        ];

        if ($this->listId !== null) {
            $payload['list_id'] = $this->listId;
        }

        if ($this->properties !== null) {
            $payload['properties'] = $this->properties;
        }

        return $payload;
    }
}
