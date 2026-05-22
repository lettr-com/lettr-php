<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Filter for paging through audience topics.
 */
final readonly class ListAudienceTopicsFilter implements Arrayable
{
    public function __construct(
        public ?int $page = null,
        public ?int $perPage = null,
    ) {}

    public static function create(): self
    {
        return new self;
    }

    public function page(int $page): self
    {
        return new self(page: $page, perPage: $this->perPage);
    }

    public function perPage(int $perPage): self
    {
        return new self(page: $this->page, perPage: $perPage);
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

        return $params;
    }
}
