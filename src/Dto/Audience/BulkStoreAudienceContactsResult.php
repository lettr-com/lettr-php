<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

/**
 * Result of a bulk contact create call.
 */
final readonly class BulkStoreAudienceContactsResult
{
    public function __construct(
        public int $created,
        public int $alreadyExisted,
    ) {}

    /**
     * @param  array{created: int, already_existed: int}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            created: $data['created'],
            alreadyExisted: $data['already_existed'],
        );
    }
}
