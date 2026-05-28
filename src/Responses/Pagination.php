<?php

declare(strict_types=1);

namespace Lettr\Responses;

/**
 * Page-based pagination metadata returned by list endpoints.
 *
 * Per-resource subclasses (e.g. {@see AudiencePagination}) exist as type
 * markers for the response wrappers that hold them; they inherit all behavior
 * from this class and add nothing.
 */
readonly class Pagination
{
    final public function __construct(
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {}

    /**
     * @param  array{
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int,
     * }  $data
     */
    public static function from(array $data): static
    {
        return new static(
            currentPage: $data['current_page'],
            lastPage: $data['last_page'],
            perPage: $data['per_page'],
            total: $data['total'],
        );
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function nextPage(): ?int
    {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }

    public function previousPage(): ?int
    {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : null;
    }
}
