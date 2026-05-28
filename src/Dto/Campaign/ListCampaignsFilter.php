<?php

declare(strict_types=1);

namespace Lettr\Dto\Campaign;

use Lettr\Contracts\Arrayable;
use Lettr\Enums\CampaignStatus;

/**
 * Filter for paging and filtering campaigns.
 */
final readonly class ListCampaignsFilter implements Arrayable
{
    public function __construct(
        public ?int $page = null,
        public ?int $perPage = null,
        public ?CampaignStatus $status = null,
    ) {}

    public static function create(): self
    {
        return new self;
    }

    public function page(int $page): self
    {
        return new self($page, $this->perPage, $this->status);
    }

    public function perPage(int $perPage): self
    {
        return new self($this->page, $perPage, $this->status);
    }

    public function status(CampaignStatus $status): self
    {
        return new self($this->page, $this->perPage, $status);
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

        if ($this->status !== null) {
            $params['status'] = $this->status->value;
        }

        return $params;
    }
}
