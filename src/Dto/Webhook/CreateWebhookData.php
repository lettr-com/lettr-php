<?php

declare(strict_types=1);

namespace Lettr\Dto\Webhook;

use Lettr\Contracts\Arrayable;
use Lettr\Enums\WebhookAuthType;
use Lettr\Enums\WebhookEventsMode;
use Lettr\Enums\WebhookEventType;

/**
 * Data for creating a new webhook.
 */
final readonly class CreateWebhookData implements Arrayable
{
    /**
     * @param  array<WebhookEventType|string>|null  $events
     */
    public function __construct(
        public string $name,
        public string $url,
        public WebhookAuthType $authType,
        public WebhookEventsMode $eventsMode,
        public ?string $authUsername = null,
        public ?string $authPassword = null,
        public ?string $oauthClientId = null,
        public ?string $oauthClientSecret = null,
        public ?string $oauthTokenUrl = null,
        public ?array $events = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'url' => $this->url,
            'auth_type' => $this->authType->value,
            'events_mode' => $this->eventsMode->value,
        ];

        if ($this->authUsername !== null) {
            $data['auth_username'] = $this->authUsername;
        }

        if ($this->authPassword !== null) {
            $data['auth_password'] = $this->authPassword;
        }

        if ($this->oauthClientId !== null) {
            $data['oauth_client_id'] = $this->oauthClientId;
        }

        if ($this->oauthClientSecret !== null) {
            $data['oauth_client_secret'] = $this->oauthClientSecret;
        }

        if ($this->oauthTokenUrl !== null) {
            $data['oauth_token_url'] = $this->oauthTokenUrl;
        }

        if ($this->events !== null) {
            $data['events'] = array_map(
                static fn (WebhookEventType|string $event): string => $event instanceof WebhookEventType
                    ? $event->value
                    : $event,
                $this->events,
            );
        }

        return $data;
    }
}
