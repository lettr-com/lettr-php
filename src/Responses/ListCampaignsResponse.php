<?php

declare(strict_types=1);

namespace Lettr\Responses;

use Lettr\Collections\CampaignCollection;
use Lettr\Dto\Campaign\CampaignSummary;

/**
 * Response from listing campaigns.
 *
 * @phpstan-import-type CampaignSummaryData from CampaignSummary
 */
final readonly class ListCampaignsResponse
{
    public function __construct(
        public CampaignCollection $campaigns,
        public Pagination $pagination,
    ) {}

    /**
     * @param  array{
     *     campaigns: array<int, CampaignSummaryData>,
     *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            campaigns: CampaignCollection::from(
                array_map(
                    static fn (array $campaign): CampaignSummary => CampaignSummary::from($campaign),
                    $data['campaigns'],
                ),
            ),
            pagination: Pagination::from($data['pagination']),
        );
    }

    public function hasMore(): bool
    {
        return $this->pagination->hasNextPage();
    }
}
