<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

/**
 * Result of a bulk list delete call.
 */
final readonly class BulkDestroyAudienceListsResult
{
    public function __construct(
        public int $deleted,
    ) {}

    /**
     * @param  array{deleted: int}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            deleted: $data['deleted'],
        );
    }
}
