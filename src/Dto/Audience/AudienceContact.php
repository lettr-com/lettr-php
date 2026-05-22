<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Enums\AudienceContactStatus;
use Lettr\ValueObjects\ContactProperties;
use Lettr\ValueObjects\Timestamp;

/**
 * Audience contact entity.
 */
final readonly class AudienceContact
{
    /**
     * @param  array<int, AudienceContactListLink>  $lists
     * @param  array<int, AudienceContactTopicLink>  $topics
     */
    public function __construct(
        public string $id,
        public string $email,
        public AudienceContactStatus $status,
        public ContactProperties $properties,
        public Timestamp $createdAt,
        public array $lists,
        public array $topics,
    ) {}

    /**
     * @param  array{
     *     id: string,
     *     email: string,
     *     status: string,
     *     properties: array<string, string>,
     *     created_at: string,
     *     lists: array<int, array{id: string, name: string}>,
     *     topics: array<int, array{id: string, name: string}>,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            id: $data['id'],
            email: $data['email'],
            status: AudienceContactStatus::from($data['status']),
            properties: ContactProperties::from($data['properties']),
            createdAt: Timestamp::fromString($data['created_at']),
            lists: array_map(
                static fn (array $link): AudienceContactListLink => AudienceContactListLink::from($link),
                $data['lists'],
            ),
            topics: array_map(
                static fn (array $link): AudienceContactTopicLink => AudienceContactTopicLink::from($link),
                $data['topics'],
            ),
        );
    }
}
