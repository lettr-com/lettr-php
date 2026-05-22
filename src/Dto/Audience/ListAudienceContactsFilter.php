<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;
use Lettr\Enums\AudienceContactStatus;

/**
 * Filter for paging and filtering audience contacts.
 */
final readonly class ListAudienceContactsFilter implements Arrayable
{
    public function __construct(
        public ?int $page = null,
        public ?int $perPage = null,
        public ?string $search = null,
        public ?AudienceContactStatus $status = null,
        public ?string $listId = null,
        public ?string $segmentId = null,
    ) {}

    public static function create(): self
    {
        return new self;
    }

    public function page(int $page): self
    {
        return new self($page, $this->perPage, $this->search, $this->status, $this->listId, $this->segmentId);
    }

    public function perPage(int $perPage): self
    {
        return new self($this->page, $perPage, $this->search, $this->status, $this->listId, $this->segmentId);
    }

    public function search(string $search): self
    {
        return new self($this->page, $this->perPage, $search, $this->status, $this->listId, $this->segmentId);
    }

    public function status(AudienceContactStatus $status): self
    {
        return new self($this->page, $this->perPage, $this->search, $status, $this->listId, $this->segmentId);
    }

    public function listId(string $listId): self
    {
        return new self($this->page, $this->perPage, $this->search, $this->status, $listId, $this->segmentId);
    }

    public function segmentId(string $segmentId): self
    {
        return new self($this->page, $this->perPage, $this->search, $this->status, $this->listId, $segmentId);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $params = [];

        if ($this->page !== null) {
            $params['page'] = $this->page;
        }

        if ($this->perPage !== null) {
            $params['per_page'] = $this->perPage;
        }

        if ($this->search !== null) {
            $params['search'] = $this->search;
        }

        if ($this->status !== null) {
            $params['status'] = $this->status->value;
        }

        if ($this->listId !== null) {
            $params['list_id'] = $this->listId;
        }

        if ($this->segmentId !== null) {
            $params['segment_id'] = $this->segmentId;
        }

        return $params;
    }
}
