<?php

declare(strict_types=1);

namespace Lettr\Services;

use Lettr\Collections\WebhookCollection;
use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Webhook\CreateWebhookData;
use Lettr\Dto\Webhook\UpdateWebhookData;
use Lettr\Dto\Webhook\Webhook;
use Lettr\ValueObjects\WebhookId;

/**
 * Service for managing webhooks via the Lettr API.
 */
final class WebhookService
{
    private const WEBHOOKS_ENDPOINT = 'webhooks';

    public function __construct(
        private readonly TransporterContract $transporter,
    ) {}

    /**
     * List all webhooks.
     */
    public function list(): WebhookCollection
    {
        /**
         * @var array{
         *     webhooks: array<int, array{
         *         id: string,
         *         name: string,
         *         url: string,
         *         enabled: bool,
         *         auth_type: string,
         *         has_auth_credentials: bool,
         *         event_types: array<string>|null,
         *         last_status?: string|null,
         *         last_successful_at?: string|null,
         *         last_failure_at?: string|null,
         *     }>
         * } $response
         */
        $response = $this->transporter->get(self::WEBHOOKS_ENDPOINT);

        $webhooks = array_map(
            static fn (array $webhook): Webhook => Webhook::from($webhook),
            $response['webhooks']
        );

        return WebhookCollection::from($webhooks);
    }

    /**
     * Get webhook details.
     */
    public function get(string|WebhookId $webhookId): Webhook
    {
        $id = $webhookId instanceof WebhookId ? (string) $webhookId : $webhookId;

        /**
         * @var array{
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
         * } $response
         */
        $response = $this->transporter->get(self::WEBHOOKS_ENDPOINT.'/'.$id);

        return Webhook::from($response);
    }

    /**
     * Create a new webhook.
     */
    public function create(CreateWebhookData $data): Webhook
    {
        /**
         * @var array{
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
         * } $response
         */
        $response = $this->transporter->post(self::WEBHOOKS_ENDPOINT, $data->toArray());

        return Webhook::from($response);
    }

    /**
     * Update an existing webhook.
     */
    public function update(string|WebhookId $webhookId, UpdateWebhookData $data): Webhook
    {
        $id = $webhookId instanceof WebhookId ? (string) $webhookId : $webhookId;

        /**
         * @var array{
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
         * } $response
         */
        $response = $this->transporter->put(self::WEBHOOKS_ENDPOINT.'/'.$id, $data->toArray());

        return Webhook::from($response);
    }

    /**
     * Delete a webhook.
     */
    public function delete(string|WebhookId $webhookId): void
    {
        $id = $webhookId instanceof WebhookId ? (string) $webhookId : $webhookId;

        $this->transporter->delete(self::WEBHOOKS_ENDPOINT.'/'.$id);
    }
}
