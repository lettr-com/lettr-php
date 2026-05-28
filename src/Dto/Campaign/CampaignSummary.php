<?php

declare(strict_types=1);

namespace Lettr\Dto\Campaign;

use Lettr\Enums\CampaignStatus;

/**
 * Campaign — list/show response payload, with embedded engagement stats.
 *
 * `$htmlContent` is populated only by `GET /campaigns/{id}` (the show
 * endpoint); list and action responses leave it `null`.
 *
 * `$status` is a {@see CampaignStatus} for spec-known values, or the raw
 * string for any value the SDK doesn't yet recognise — so a server-side
 * enum extension never crashes deserialisation.
 *
 * @phpstan-import-type CampaignStatsData from CampaignStats
 *
 * @phpstan-type CampaignSummaryData array{
 *     id: string,
 *     name: string,
 *     subject?: string|null,
 *     from_email?: string|null,
 *     from_name?: string|null,
 *     reply_to?: string|null,
 *     status: string,
 *     scheduled_at?: string|null,
 *     total_recipients?: int|null,
 *     sent_count: int,
 *     sent_at?: string|null,
 *     created_at: string,
 *     stats: CampaignStatsData,
 *     html_content?: string|null,
 * }
 */
final readonly class CampaignSummary
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $subject,
        public ?string $fromEmail,
        public ?string $fromName,
        public ?string $replyTo,
        public CampaignStatus|string $status,
        public ?string $scheduledAt,
        public ?int $totalRecipients,
        public int $sentCount,
        public ?string $sentAt,
        public string $createdAt,
        public CampaignStats $stats,
        public ?string $htmlContent = null,
    ) {}

    /**
     * @param  CampaignSummaryData  $data
     */
    public static function from(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            subject: $data['subject'] ?? null,
            fromEmail: $data['from_email'] ?? null,
            fromName: $data['from_name'] ?? null,
            replyTo: $data['reply_to'] ?? null,
            status: CampaignStatus::tryFrom($data['status']) ?? $data['status'],
            scheduledAt: $data['scheduled_at'] ?? null,
            totalRecipients: $data['total_recipients'] ?? null,
            sentCount: $data['sent_count'],
            sentAt: $data['sent_at'] ?? null,
            createdAt: $data['created_at'],
            stats: CampaignStats::from($data['stats']),
            htmlContent: $data['html_content'] ?? null,
        );
    }
}
