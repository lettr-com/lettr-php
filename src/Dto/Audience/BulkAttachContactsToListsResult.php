<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

/**
 * Result of a bulk contact-to-lists attach call.
 */
final readonly class BulkAttachContactsToListsResult
{
    public function __construct(
        public int $attached,
        public int $alreadyAttached,
        public int $totalPairs,
    ) {}

    /**
     * @param  array{attached: int, already_attached: int, total_pairs: int}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            attached: $data['attached'],
            alreadyAttached: $data['already_attached'],
            totalPairs: $data['total_pairs'],
        );
    }
}
