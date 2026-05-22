<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Enums\AudienceTopicDefaultSubscription;
use Lettr\Enums\AudienceTopicVisibility;
use Lettr\ValueObjects\Timestamp;

/**
 * Audience topic entity.
 */
final readonly class AudienceTopic
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public AudienceTopicDefaultSubscription $defaultSubscription,
        public AudienceTopicVisibility $visibility,
        public int $contactsCount,
        public ?Timestamp $createdAt,
    ) {}

    /**
     * @param  array{
     *     id: string,
     *     name: string,
     *     description: string|null,
     *     default_subscription: string,
     *     visibility: string,
     *     contacts_count: int,
     *     created_at: string|null,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            description: $data['description'],
            defaultSubscription: AudienceTopicDefaultSubscription::from($data['default_subscription']),
            visibility: AudienceTopicVisibility::from($data['visibility']),
            contactsCount: $data['contacts_count'],
            createdAt: $data['created_at'] !== null ? Timestamp::fromString($data['created_at']) : null,
        );
    }
}
