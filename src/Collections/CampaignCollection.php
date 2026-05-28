<?php

declare(strict_types=1);

namespace Lettr\Collections;

use Lettr\Dto\Campaign\CampaignSummary;

/**
 * @extends Collection<CampaignSummary>
 */
final readonly class CampaignCollection extends Collection
{
    public function findById(string $id): ?CampaignSummary
    {
        foreach ($this->items as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }

        return null;
    }
}
