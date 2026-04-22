<?php

declare(strict_types=1);

namespace Lettr\Dto\Webhook;

use Lettr\Collections\WebhookEventTypeCollection;
use Lettr\Enums\WebhookAuthType;
use Lettr\Enums\WebhookEventType;
use Lettr\Enums\WebhookStatus;
use Lettr\ValueObjects\Timestamp;
use Lettr\ValueObjects\WebhookId;

/**
 * Webhook configuration.
 */
final readonly class Webhook
{
    public function __construct(
        public WebhookId $id,
        public string $name,
        public string $url,
        public bool $enabled,
        public WebhookAuthType $authType,
        public bool $hasAuthCredentials,
        public ?WebhookEventTypeCollection $eventTypes,
        public ?WebhookStatus $lastStatus = null,
        public ?Timestamp $lastSuccessfulAt = null,
        public ?Timestamp $lastFailureAt = null,
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array{
     *     id: string,
     *     name: string,
     *     url: string,
     *     enabled: bool,
     *     auth_type: string,
     *     has_auth_credentials: bool,
     *     event_types: array<string>|null,
     *     last_status?: string|null,
     *     last_successful_at?: string|null,
     *     last_failure_at?: string|null,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            id: new WebhookId($data['id']),
            name: $data['name'],
            url: $data['url'],
            enabled: $data['enabled'],
            authType: WebhookAuthType::from($data['auth_type']),
            hasAuthCredentials: $data['has_auth_credentials'],
            eventTypes: $data['event_types'] !== null
                ? WebhookEventTypeCollection::from($data['event_types'])
                : null,
            lastStatus: isset($data['last_status']) ? WebhookStatus::from($data['last_status']) : null,
            lastSuccessfulAt: isset($data['last_successful_at']) ? Timestamp::fromString($data['last_successful_at']) : null,
            lastFailureAt: isset($data['last_failure_at']) ? Timestamp::fromString($data['last_failure_at']) : null,
        );
    }

    /**
     * Check if the webhook is currently working.
     */
    public function isHealthy(): bool
    {
        return $this->enabled
            && ($this->lastStatus === null || $this->lastStatus === WebhookStatus::Success);
    }

    /**
     * Check if the webhook is failing.
     */
    public function isFailing(): bool
    {
        return $this->lastStatus === WebhookStatus::Failure;
    }

    /**
     * Check if the webhook listens to a specific event type.
     *
     * A null `eventTypes` means the webhook is subscribed to every event.
     */
    public function listensTo(WebhookEventType $type): bool
    {
        return $this->eventTypes === null || $this->eventTypes->contains($type);
    }

    /**
     * Whether the webhook is subscribed to every event (API returned `event_types: null`).
     */
    public function listensToAllEvents(): bool
    {
        return $this->eventTypes === null;
    }
}
