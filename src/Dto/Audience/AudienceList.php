<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

/**
 * Audience list entity.
 */
final readonly class AudienceList
{
    public function __construct(
        public string $id,
        public string $name,
        public int $contactsCount,
    ) {}

    /**
     * @param  array{
     *     id: string,
     *     name: string,
     *     contacts_count: int,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            contactsCount: $data['contacts_count'],
        );
    }
}
