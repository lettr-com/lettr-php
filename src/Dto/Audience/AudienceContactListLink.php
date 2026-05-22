<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

/**
 * Lightweight link from an audience contact back to a list it belongs to.
 */
final readonly class AudienceContactListLink
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    /**
     * @param  array{id: string, name: string}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
        );
    }
}
