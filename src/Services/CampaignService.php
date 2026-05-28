<?php

declare(strict_types=1);

namespace Lettr\Services;

use DateTimeInterface;
use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Campaign\CampaignDetail;
use Lettr\Dto\Campaign\CampaignSummary;
use Lettr\Dto\Campaign\ListCampaignEventsFilter;
use Lettr\Dto\Campaign\ListCampaignsFilter;
use Lettr\Responses\ListCampaignEventsResponse;
use Lettr\Responses\ListCampaignsResponse;

/**
 * Service for reading campaigns and managing their delivery via the Lettr API.
 *
 * @phpstan-import-type CampaignSummaryData from CampaignSummary
 * @phpstan-import-type CampaignDetailData from CampaignDetail
 * @phpstan-import-type CampaignEventData from \Lettr\Dto\Campaign\CampaignEvent
 */
final class CampaignService
{
    private const ENDPOINT = 'campaigns';

    public function __construct(
        private readonly TransporterContract $transporter,
    ) {}

    /**
     * List campaigns with embedded engagement stats.
     */
    public function list(?ListCampaignsFilter $filter = null): ListCampaignsResponse
    {
        $query = $filter?->toArray() ?? [];

        /**
         * @var array{
         *     campaigns: array<int, CampaignSummaryData>,
         *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
         * } $response
         */
        $response = $this->transporter->getWithQuery(self::ENDPOINT, $query);

        return ListCampaignsResponse::from($response);
    }

    /**
     * Get a single campaign with rendered HTML content (`$campaign->htmlContent`).
     */
    public function get(string $campaignId): CampaignDetail
    {
        /** @var CampaignDetailData $response */
        $response = $this->transporter->get(self::ENDPOINT.'/'.$campaignId);

        return CampaignDetail::from($response);
    }

    /**
     * List a campaign's engagement events (cursor-paginated).
     */
    public function events(string $campaignId, ?ListCampaignEventsFilter $filter = null): ListCampaignEventsResponse
    {
        $query = $filter?->toArray() ?? [];

        /**
         * @var array{
         *     events: array<int, CampaignEventData>,
         *     next_cursor?: string|null,
         * } $response
         */
        $response = $this->transporter->getWithQuery(self::ENDPOINT.'/'.$campaignId.'/events', $query);

        return ListCampaignEventsResponse::from($response);
    }

    /**
     * Send a draft campaign immediately.
     *
     * Returns the updated campaign. In the rare case the API omits the
     * campaign payload from the action response, the SDK refetches it via
     * `GET /campaigns/{id}` so the caller never sees null.
     */
    public function send(string $campaignId): CampaignSummary
    {
        $envelope = $this->transporter->postExpectingEnvelope(
            self::ENDPOINT.'/'.$campaignId.'/send',
        );

        return $this->campaignFromActionEnvelope($envelope, $campaignId);
    }

    /**
     * Schedule a campaign for future delivery, or reschedule an
     * already-scheduled campaign to a new time.
     *
     * Returns the updated campaign (refetched if the action response omits it).
     */
    public function schedule(string $campaignId, DateTimeInterface|string $scheduledAt): CampaignSummary
    {
        $value = $scheduledAt instanceof DateTimeInterface
            ? $scheduledAt->format(DateTimeInterface::ATOM)
            : $scheduledAt;

        $envelope = $this->transporter->postExpectingEnvelope(
            self::ENDPOINT.'/'.$campaignId.'/schedule',
            ['scheduled_at' => $value],
        );

        return $this->campaignFromActionEnvelope($envelope, $campaignId);
    }

    /**
     * Cancel a scheduled send, returning the campaign to draft.
     *
     * Returns the updated campaign (refetched if the action response omits it).
     */
    public function unschedule(string $campaignId): CampaignSummary
    {
        $envelope = $this->transporter->postExpectingEnvelope(
            self::ENDPOINT.'/'.$campaignId.'/unschedule',
        );

        return $this->campaignFromActionEnvelope($envelope, $campaignId);
    }

    /**
     * Resolve a CampaignActionResponse envelope (`{message, data?: CampaignSummary}`)
     * to a non-null CampaignSummary, refetching when the API omits `data` or
     * returns a payload that isn't a complete campaign (lacking the required
     * `id` + `stats` keys). The refetch upcasts the detail to a summary so
     * action results never expose `htmlContent` — that field belongs to
     * `get()` only.
     *
     * @param  array<string, mixed>  $envelope
     */
    private function campaignFromActionEnvelope(array $envelope, string $campaignId): CampaignSummary
    {
        $payload = $envelope['data'] ?? null;

        if (is_array($payload) && isset($payload['id'], $payload['stats'])) {
            /** @var CampaignSummaryData $payload */
            return CampaignSummary::from($payload);
        }

        $detail = $this->get($campaignId);

        return new CampaignSummary(
            id: $detail->id,
            name: $detail->name,
            subject: $detail->subject,
            fromEmail: $detail->fromEmail,
            fromName: $detail->fromName,
            replyTo: $detail->replyTo,
            status: $detail->status,
            scheduledAt: $detail->scheduledAt,
            totalRecipients: $detail->totalRecipients,
            sentCount: $detail->sentCount,
            sentAt: $detail->sentAt,
            createdAt: $detail->createdAt,
            stats: $detail->stats,
        );
    }
}
