<?php

declare(strict_types=1);

namespace Lettr\Dto\Template;

use Lettr\Contracts\Arrayable;
use Lettr\Enums\TemplatePurpose;

/**
 * Filter parameters for listing templates.
 */
final readonly class ListTemplatesFilter implements Arrayable
{
    public function __construct(
        public ?int $projectId = null,
        public ?int $perPage = null,
        public ?int $page = null,
        public ?TemplatePurpose $purpose = null,
        public ?int $folderId = null,
    ) {}

    /**
     * Create a new filter.
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Set the project ID.
     */
    public function projectId(int $projectId): self
    {
        return new self(
            projectId: $projectId,
            perPage: $this->perPage,
            page: $this->page,
            purpose: $this->purpose,
            folderId: $this->folderId,
        );
    }

    /**
     * Narrow the list to one folder of the resolved project.
     *
     * This is what makes reconciling a bulk import cheap: one `perPage(100)`
     * call for the whole folder instead of a detail call per template, each of
     * which drags the full HTML payload against the same rate limit.
     *
     * A folder that is not in the resolved project is a 404, not an empty list.
     */
    public function folderId(int $folderId): self
    {
        return new self(
            projectId: $this->projectId,
            perPage: $this->perPage,
            page: $this->page,
            purpose: $this->purpose,
            folderId: $folderId,
        );
    }

    /**
     * Narrow the list to one module. Absent means both.
     */
    public function purpose(TemplatePurpose $purpose): self
    {
        return new self(
            projectId: $this->projectId,
            perPage: $this->perPage,
            page: $this->page,
            purpose: $purpose,
            folderId: $this->folderId,
        );
    }

    /**
     * Set items per page.
     */
    public function perPage(int $perPage): self
    {
        return new self(
            projectId: $this->projectId,
            perPage: $perPage,
            page: $this->page,
            purpose: $this->purpose,
            folderId: $this->folderId,
        );
    }

    /**
     * Set the page number.
     */
    public function page(int $page): self
    {
        return new self(
            projectId: $this->projectId,
            perPage: $this->perPage,
            page: $page,
            purpose: $this->purpose,
            folderId: $this->folderId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $params = [];

        if ($this->projectId !== null) {
            $params['project_id'] = $this->projectId;
        }

        if ($this->folderId !== null) {
            $params['folder_id'] = $this->folderId;
        }

        if ($this->purpose !== null) {
            $params['purpose'] = $this->purpose->value;
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
        return $this->projectId !== null
            || $this->folderId !== null
            || $this->purpose !== null
            || $this->perPage !== null
            || $this->page !== null;
    }
}
