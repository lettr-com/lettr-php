<?php

declare(strict_types=1);

namespace Lettr\Services\Audience;

use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Audience\AudienceList;
use Lettr\Dto\Audience\BulkDeleteAudienceListsData;
use Lettr\Dto\Audience\BulkDestroyAudienceListsResult;
use Lettr\Dto\Audience\CreateAudienceListData;
use Lettr\Dto\Audience\ListAudienceListsFilter;
use Lettr\Dto\Audience\UpdateAudienceListData;
use Lettr\Responses\ListAudienceListsResponse;

/**
 * Service for managing audience lists via the Lettr API.
 */
final class AudienceListService
{
    private const ENDPOINT = 'audience/lists';

    public function __construct(
        private readonly TransporterContract $transporter,
    ) {}

    public function list(?ListAudienceListsFilter $filter = null): ListAudienceListsResponse
    {
        $query = $filter?->toArray() ?? [];

        /**
         * @var array{
         *     lists: array<int, array{id: string, name: string, contacts_count: int}>,
         *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
         * } $response
         */
        $response = $this->transporter->getWithQuery(self::ENDPOINT, $query);

        return ListAudienceListsResponse::from($response);
    }

    public function get(string $listId): AudienceList
    {
        /**
         * @var array{id: string, name: string, contacts_count: int} $response
         */
        $response = $this->transporter->get(self::ENDPOINT.'/'.$listId);

        return AudienceList::from($response);
    }

    public function create(CreateAudienceListData $data): AudienceList
    {
        /**
         * @var array{id: string, name: string, contacts_count: int} $response
         */
        $response = $this->transporter->post(self::ENDPOINT, $data->toArray());

        return AudienceList::from($response);
    }

    public function update(string $listId, UpdateAudienceListData $data): AudienceList
    {
        /**
         * @var array{id: string, name: string, contacts_count: int} $response
         */
        $response = $this->transporter->patch(self::ENDPOINT.'/'.$listId, $data->toArray());

        return AudienceList::from($response);
    }

    public function delete(string $listId): void
    {
        $this->transporter->delete(self::ENDPOINT.'/'.$listId);
    }

    public function bulkDelete(BulkDeleteAudienceListsData $data): BulkDestroyAudienceListsResult
    {
        /**
         * @var array{deleted: int} $response
         */
        $response = $this->transporter->deleteWithBody(self::ENDPOINT.'/bulk', $data->toArray());

        return BulkDestroyAudienceListsResult::from($response);
    }
}
