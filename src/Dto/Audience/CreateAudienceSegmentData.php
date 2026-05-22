<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Request body for creating an audience segment.
 */
final readonly class CreateAudienceSegmentData implements Arrayable
{
    public function __construct(
        public string $name,
        public SegmentConditionsInput $conditions,
        public ?string $listId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'name' => $this->name,
            'conditions' => $this->conditions->toArray(),
        ];

        if ($this->listId !== null) {
            $payload['list_id'] = $this->listId;
        }

        return $payload;
    }
}
