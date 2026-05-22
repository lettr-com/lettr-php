<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Request body for bulk deleting audience lists (1-50 IDs).
 */
final readonly class BulkDeleteAudienceListsData implements Arrayable
{
    /**
     * @param  array<int, string>  $listIds
     */
    public function __construct(
        public array $listIds,
    ) {}

    /**
     * @return array{list_ids: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'list_ids' => array_values($this->listIds),
        ];
    }
}
