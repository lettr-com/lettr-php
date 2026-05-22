<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Request body for partially updating an audience segment (PATCH).
 *
 * To clear the list restriction (segment applies to all lists), use
 * `withClearedListId()`.
 */
final readonly class UpdateAudienceSegmentData implements Arrayable
{
    private function __construct(
        public ?string $name,
        public ?string $listId,
        private bool $sendListId,
        public ?SegmentConditionsInput $conditions,
    ) {}

    public static function empty(): self
    {
        return new self(null, null, false, null);
    }

    public function withName(string $name): self
    {
        return new self($name, $this->listId, $this->sendListId, $this->conditions);
    }

    public function withListId(string $listId): self
    {
        return new self($this->name, $listId, true, $this->conditions);
    }

    public function withClearedListId(): self
    {
        return new self($this->name, null, true, $this->conditions);
    }

    public function withConditions(SegmentConditionsInput $conditions): self
    {
        return new self($this->name, $this->listId, $this->sendListId, $conditions);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [];

        if ($this->name !== null) {
            $payload['name'] = $this->name;
        }

        if ($this->sendListId) {
            $payload['list_id'] = $this->listId;
        }

        if ($this->conditions !== null) {
            $payload['conditions'] = $this->conditions->toArray();
        }

        return $payload;
    }
}
