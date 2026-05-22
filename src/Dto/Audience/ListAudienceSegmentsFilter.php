<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Filter for paging and filtering audience segments.
 */
final readonly class ListAudienceSegmentsFilter implements Arrayable
{
    public function __construct(
        public ?int $page = null,
        public ?int $perPage = null,
        public ?string $listId = null,
    ) {}

    public static function create(): self
    {
        return new self;
    }

    public function page(int $page): self
    {
        return new self($page, $this->perPage, $this->listId);
    }

    public function perPage(int $perPage): self
    {
        return new self($this->page, $perPage, $this->listId);
    }

    public function listId(string $listId): self
    {
        return new self($this->page, $this->perPage, $listId);
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

        if ($this->listId !== null) {
            $params['list_id'] = $this->listId;
        }

        return $params;
    }
}
