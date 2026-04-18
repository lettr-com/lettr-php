<?php

declare(strict_types=1);

namespace Lettr\Dto\Webhook;

use Lettr\Contracts\Arrayable;
use Lettr\Enums\WebhookAuthType;
use Lettr\Enums\WebhookEventType;

/**
 * Data for updating an existing webhook.
 *
 * Note: the spec's update request uses the field name `target` (not `url`)
 * to carry the destination URL — matching the underlying API's naming.
 */
final readonly class UpdateWebhookData implements Arrayable
{
    /**
     * @param  array<WebhookEventType|string>|null  $events
     */
    public function __construct(
        public ?string $name = null,
        public ?string $target = null,
        public ?WebhookAuthType $authType = null,
        public ?string $authUsername = null,
        public ?string $authPassword = null,
        public ?string $oauthTokenUrl = null,
        public ?string $oauthClientId = null,
        public ?string $oauthClientSecret = null,
        public ?array $events = null,
        public ?bool $active = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->target !== null) {
            $data['target'] = $this->target;
        }

        if ($this->authType !== null) {
            $data['auth_type'] = $this->authType->value;
        }

        if ($this->authUsername !== null) {
            $data['auth_username'] = $this->authUsername;
        }

        if ($this->authPassword !== null) {
            $data['auth_password'] = $this->authPassword;
        }

        if ($this->oauthTokenUrl !== null) {
            $data['oauth_token_url'] = $this->oauthTokenUrl;
        }

        if ($this->oauthClientId !== null) {
            $data['oauth_client_id'] = $this->oauthClientId;
        }

        if ($this->oauthClientSecret !== null) {
            $data['oauth_client_secret'] = $this->oauthClientSecret;
        }

        if ($this->events !== null) {
            $data['events'] = array_map(
                static fn (WebhookEventType|string $event): string => $event instanceof WebhookEventType
                    ? $event->value
                    : $event,
                $this->events,
            );
        }

        if ($this->active !== null) {
            $data['active'] = $this->active;
        }

        return $data;
    }
}
