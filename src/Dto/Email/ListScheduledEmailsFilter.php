<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

use Lettr\Contracts\Arrayable;
use Lettr\Enums\ScheduledEmailState;

/**
 * Filter parameters for listing scheduled emails.
 */
final readonly class ListScheduledEmailsFilter implements Arrayable
{
    public function __construct(
        public ?ScheduledEmailState $status = null,
        public ?int $perPage = null,
        public ?int $page = null,
    ) {}

    /**
     * Create a new filter.
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Only return scheduled emails in this state.
     */
    public function status(ScheduledEmailState $status): self
    {
        return new self(
            status: $status,
            perPage: $this->perPage,
            page: $this->page,
        );
    }

    /**
     * Set items per page (1-100).
     */
    public function perPage(int $perPage): self
    {
        return new self(
            status: $this->status,
            perPage: $perPage,
            page: $this->page,
        );
    }

    /**
     * Set the page number.
     */
    public function page(int $page): self
    {
        return new self(
            status: $this->status,
            perPage: $this->perPage,
            page: $page,
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        $params = [];

        if ($this->status !== null) {
            $params['status'] = $this->status->value;
        }

        if ($this->perPage !== null) {
            $params['per_page'] = $this->perPage;
        }

        if ($this->page !== null) {
            $params['page'] = $this->page;
        }

        return $params;
    }

    /**
     * Check if any filters are set.
     */
    public function hasFilters(): bool
    {
        return $this->status !== null
            || $this->perPage !== null
            || $this->page !== null;
    }
}
