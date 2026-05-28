<?php

declare(strict_types=1);

namespace Lettr\Dto\Campaign;

/**
 * Aggregated engagement statistics for a campaign.
 *
 * @phpstan-type CampaignStatsData array{
 *     injections: int,
 *     deliveries: int,
 *     bounces: int,
 *     spam_complaints: int,
 *     opens: int,
 *     unique_opens: int,
 *     clicks: int,
 *     unique_clicks: int,
 *     unsubscribes: int,
 * }
 */
final readonly class CampaignStats
{
    public function __construct(
        public int $injections,
        public int $deliveries,
        public int $bounces,
        public int $spamComplaints,
        public int $opens,
        public int $uniqueOpens,
        public int $clicks,
        public int $uniqueClicks,
        public int $unsubscribes,
    ) {}

    /**
     * @param  CampaignStatsData  $data
     */
    public static function from(array $data): self
    {
        return new self(
            injections: $data['injections'],
            deliveries: $data['deliveries'],
            bounces: $data['bounces'],
            spamComplaints: $data['spam_complaints'],
            opens: $data['opens'],
            uniqueOpens: $data['unique_opens'],
            clicks: $data['clicks'],
            uniqueClicks: $data['unique_clicks'],
            unsubscribes: $data['unsubscribes'],
        );
    }
}
