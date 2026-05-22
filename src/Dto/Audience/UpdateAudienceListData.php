<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Request body for partially updating an audience list (PATCH).
 *
 * Omit fields you don't want to change. Passing an empty payload returns
 * the existing list unchanged.
 */
final readonly class UpdateAudienceListData implements Arrayable
{
    public function __construct(
        public ?string $name = null,
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

        return $payload;
    }
}
