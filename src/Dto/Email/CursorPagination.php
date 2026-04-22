<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

/**
 * Cursor-based pagination data.
 */
final readonly class CursorPagination
{
    public function __construct(
        public ?string $nextCursor,
        public int $perPage,
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array{next_cursor?: string|null, per_page?: int}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            nextCursor: $data['next_cursor'] ?? null,
            perPage: $data['per_page'] ?? 25,
        );
    }

    /**
     * Check if there is a next page.
     */
    public function hasNextPage(): bool
    {
        return $this->nextCursor !== null;
    }
}
