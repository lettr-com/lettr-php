<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Request body for attaching every (contact × list) combination in bulk
 * (up to 1000 contacts × 50 lists).
 */
final readonly class BulkAttachContactsToListsData implements Arrayable
{
    /**
     * @param  array<int, string>  $contactIds
     * @param  array<int, string>  $listIds
     */
    public function __construct(
        public array $contactIds,
        public array $listIds,
    ) {}

    /**
     * @return array{contact_ids: array<int, string>, list_ids: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'contact_ids' => array_values($this->contactIds),
            'list_ids' => array_values($this->listIds),
        ];
    }
}
