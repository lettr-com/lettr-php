<?php

declare(strict_types=1);

namespace Lettr\Services\Audience;

use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Audience\AudienceContact;
use Lettr\Dto\Audience\BulkAttachContactsToListsData;
use Lettr\Dto\Audience\BulkAttachContactsToListsResult;
use Lettr\Dto\Audience\BulkCreateAudienceContactsData;
use Lettr\Dto\Audience\BulkDetachContactsFromListsData;
use Lettr\Dto\Audience\BulkDetachContactsFromListsResult;
use Lettr\Dto\Audience\BulkStoreAudienceContactsResult;
use Lettr\Dto\Audience\CreateAudienceContactData;
use Lettr\Dto\Audience\ListAudienceContactsFilter;
use Lettr\Dto\Audience\UpdateAudienceContactData;
use Lettr\Responses\ListAudienceContactsResponse;

/**
 * Service for managing audience contacts via the Lettr API.
 */
final class AudienceContactService
{
    private const ENDPOINT = 'audience/contacts';

    public function __construct(
        private readonly TransporterContract $transporter,
    ) {}

    public function list(?ListAudienceContactsFilter $filter = null): ListAudienceContactsResponse
    {
        $query = $filter?->toArray() ?? [];

        /**
         * @var array{
         *     contacts: array<int, array{
         *         id: string,
         *         email: string,
         *         status: string,
         *         properties: array<string, string>,
         *         created_at: string,
         *         lists: array<int, array{id: string, name: string}>,
         *         topics: array<int, array{id: string, name: string}>,
         *     }>,
         *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
         * } $response
         */
        $response = $this->transporter->getWithQuery(self::ENDPOINT, $query);

        return ListAudienceContactsResponse::from($response);
    }

    public function get(string $contactId): AudienceContact
    {
        /**
         * @var array{
         *     id: string,
         *     email: string,
         *     status: string,
         *     properties: array<string, string>,
         *     created_at: string,
         *     lists: array<int, array{id: string, name: string}>,
         *     topics: array<int, array{id: string, name: string}>,
         * } $response
         */
        $response = $this->transporter->get(self::ENDPOINT.'/'.$contactId);

        return AudienceContact::from($response);
    }

    public function create(CreateAudienceContactData $data): AudienceContact
    {
        /**
         * @var array{
         *     id: string,
         *     email: string,
         *     status: string,
         *     properties: array<string, string>,
         *     created_at: string,
         *     lists: array<int, array{id: string, name: string}>,
         *     topics: array<int, array{id: string, name: string}>,
         * } $response
         */
        $response = $this->transporter->post(self::ENDPOINT, $data->toArray());

        return AudienceContact::from($response);
    }

    public function update(string $contactId, UpdateAudienceContactData $data): AudienceContact
    {
        /**
         * @var array{
         *     id: string,
         *     email: string,
         *     status: string,
         *     properties: array<string, string>,
         *     created_at: string,
         *     lists: array<int, array{id: string, name: string}>,
         *     topics: array<int, array{id: string, name: string}>,
         * } $response
         */
        $response = $this->transporter->patch(self::ENDPOINT.'/'.$contactId, $data->toArray());

        return AudienceContact::from($response);
    }

    public function delete(string $contactId): void
    {
        $this->transporter->delete(self::ENDPOINT.'/'.$contactId);
    }

    public function bulkCreate(BulkCreateAudienceContactsData $data): BulkStoreAudienceContactsResult
    {
        /**
         * @var array{created: int, already_existed: int} $response
         */
        $response = $this->transporter->post(self::ENDPOINT.'/bulk', $data->toArray());

        return BulkStoreAudienceContactsResult::from($response);
    }

    public function bulkAttachLists(BulkAttachContactsToListsData $data): BulkAttachContactsToListsResult
    {
        /**
         * @var array{attached: int, already_attached: int, total_pairs: int} $response
         */
        $response = $this->transporter->post(self::ENDPOINT.'/lists/bulk', $data->toArray());

        return BulkAttachContactsToListsResult::from($response);
    }

    public function bulkDetachLists(BulkDetachContactsFromListsData $data): BulkDetachContactsFromListsResult
    {
        /**
         * @var array{detached: int, not_present: int, total_pairs: int} $response
         */
        $response = $this->transporter->deleteWithBody(self::ENDPOINT.'/lists/bulk', $data->toArray());

        return BulkDetachContactsFromListsResult::from($response);
    }

    /**
     * Attach a contact to a list.
     *
     * @return bool `true` if the contact was newly attached (HTTP 201),
     *              `false` if the contact was already in the list (HTTP 200).
     */
    public function attachList(string $contactId, string $listId): bool
    {
        $this->transporter->post(self::ENDPOINT.'/'.$contactId.'/lists/'.$listId, []);

        return $this->transporter->lastStatusCode() === 201;
    }

    public function detachList(string $contactId, string $listId): void
    {
        $this->transporter->delete(self::ENDPOINT.'/'.$contactId.'/lists/'.$listId);
    }

    /**
     * Subscribe a contact to a topic.
     *
     * @return bool `true` if the subscription is new (HTTP 201),
     *              `false` if the contact was already subscribed (HTTP 200).
     */
    public function subscribeTopic(string $contactId, string $topicId): bool
    {
        $this->transporter->post(self::ENDPOINT.'/'.$contactId.'/topics/'.$topicId, []);

        return $this->transporter->lastStatusCode() === 201;
    }

    public function unsubscribeTopic(string $contactId, string $topicId): void
    {
        $this->transporter->delete(self::ENDPOINT.'/'.$contactId.'/topics/'.$topicId);
    }
}
