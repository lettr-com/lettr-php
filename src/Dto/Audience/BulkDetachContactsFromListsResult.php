<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

/**
 * Result of a bulk contact-from-lists detach call.
 */
final readonly class BulkDetachContactsFromListsResult
{
    public function __construct(
        public int $detached,
        public int $notPresent,
        public int $totalPairs,
    ) {}

    /**
     * @param  array{detached: int, not_present: int, total_pairs: int}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            detached: $data['detached'],
            notPresent: $data['not_present'],
            totalPairs: $data['total_pairs'],
        );
    }
}
