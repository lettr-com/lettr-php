<?php

declare(strict_types=1);

namespace Lettr\Dto\Campaign;

use Lettr\Enums\CampaignStatus;

/**
 * Campaign with rendered HTML body — returned only by
 * `GET /campaigns/{id}` (i.e. `CampaignService::get()`).
 *
 * Extends {@see CampaignSummary} so a detail can be used anywhere a
 * summary is expected. Action endpoints (`send`/`schedule`/`unschedule`)
 * return a plain `CampaignSummary` (no `htmlContent`).
 *
 * @phpstan-import-type CampaignStatsData from CampaignStats
 * @phpstan-import-type CampaignSummaryData from CampaignSummary
 *
 * @phpstan-type CampaignDetailData array{
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
final readonly class CampaignDetail extends CampaignSummary
{
    public function __construct(
        string $id,
        string $name,
        ?string $subject,
        ?string $fromEmail,
        ?string $fromName,
        ?string $replyTo,
        CampaignStatus|string $status,
        ?string $scheduledAt,
        ?int $totalRecipients,
        int $sentCount,
        ?string $sentAt,
        string $createdAt,
        CampaignStats $stats,
        public ?string $htmlContent,
    ) {
        parent::__construct(
            id: $id,
            name: $name,
            subject: $subject,
            fromEmail: $fromEmail,
            fromName: $fromName,
            replyTo: $replyTo,
            status: $status,
            scheduledAt: $scheduledAt,
            totalRecipients: $totalRecipients,
            sentCount: $sentCount,
            sentAt: $sentAt,
            createdAt: $createdAt,
            stats: $stats,
        );
    }

    /**
     * @param  CampaignDetailData  $data
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
