<?php

declare(strict_types=1);

namespace Lettr\Services\Audience;

use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Audience\AudienceContact;
use Lettr\Dto\Audience\BulkAttachContactsToListsData;
use Lettr\Dto\Audience\BulkAttachContactsToListsResult;
use Lettr\Dto\Audience\BulkAudienceContactError;
use Lettr\Dto\Audience\BulkAudienceContactTopicsData;
use Lettr\Dto\Audience\BulkCreateAudienceContactsData;
use Lettr\Dto\Audience\BulkDetachContactsFromListsData;
use Lettr\Dto\Audience\BulkDetachContactsFromListsResult;
use Lettr\Dto\Audience\BulkStoreAudienceContactsResult;
use Lettr\Dto\Audience\BulkSubscribeContactsToTopicsResult;
use Lettr\Dto\Audience\BulkUnsubscribeContactsFromTopicsResult;
use Lettr\Dto\Audience\CreateAudienceContactData;
use Lettr\Dto\Audience\ListAudienceContactsFilter;
use Lettr\Dto\Audience\UpdateAudienceContactData;
use Lettr\Enums\ErrorCode;
use Lettr\Exceptions\ConflictException;
use Lettr\Exceptions\ContactAlreadyExistsException;
use Lettr\Exceptions\LettrException;
use Lettr\Exceptions\PartialBulkFailureException;
use Lettr\Responses\ListAudienceContactsResponse;

/**
 * Service for managing audience contacts via the Lettr API.
 */
final class AudienceContactService
{
    private const ENDPOINT = 'audience/contacts';

    /** The API's per-request cap on contacts in a bulk call. */
    private const BULK_CHUNK_SIZE = 1000;

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

    /**
     * Create a single contact.
     *
     * @throws ContactAlreadyExistsException when the email is already in the
     *                                       team's audience. It is a subclass of
     *                                       ConflictException, so existing
     *                                       handlers keep catching it.
     */
    public function create(CreateAudienceContactData $data): AudienceContact
    {
        try {
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
        } catch (ConflictException $e) {
            // The only documented 409 on this endpoint is a duplicate email.
            // Any other conflict code the API grows later stays generic.
            if ($e->errorCode === null || $e->errorCode === ErrorCode::ResourceAlreadyExists->value) {
                throw ContactAlreadyExistsException::fromConflict($e, $data->email);
            }

            throw $e;
        }

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

    /**
     * Create contacts in bulk.
     *
     * The API takes up to 1000 contacts per request. Larger inputs are split
     * into chunks of 1000, sent one after another, and merged into a single
     * result: counts are summed, `contacts` keep submission order, and each
     * error's `index` points at the row in *your* input, not in its chunk.
     *
     * Rows that fail validation are skipped, not fatal: the call still returns
     * HTTP 201 and reports them in the result's `errors`. Check
     * {@see BulkStoreAudienceContactsResult::hasErrors()} — a successful return
     * does not mean every row landed.
     *
     * Chunks are applied in order, so an address repeated across chunks is
     * counted as `alreadyExisted` the second time.
     *
     * @throws PartialBulkFailureException when a chunk fails after earlier chunks were saved.
     */
    public function bulkCreate(BulkCreateAudienceContactsData $data): BulkStoreAudienceContactsResult
    {
        $send = fn (BulkCreateAudienceContactsData $chunk): BulkStoreAudienceContactsResult => $this->sendBulkCreate($chunk);

        // Both shapes at once: there is no single row order to split by, so
        // send it as given and let the API decide.
        if ($data->emails !== [] && $data->contacts !== null && $data->contacts !== []) {
            return $send($data);
        }

        if ($data->emails !== []) {
            return $this->inChunks(
                $data->emails,
                fn (array $emails, int $offset): BulkStoreAudienceContactsResult => self::offsetErrors(
                    $send(new BulkCreateAudienceContactsData(
                        emails: $emails,
                        listId: $data->listId,
                        properties: $data->properties,
                        listIds: $data->listIds,
                        topics: $data->topics,
                        updateExisting: $data->updateExisting,
                    )),
                    $offset,
                ),
                self::mergeStoreResults(...),
                fn () => $send($data),
            );
        }

        return $this->inChunks(
            $data->contacts ?? [],
            fn (array $contacts, int $offset): BulkStoreAudienceContactsResult => self::offsetErrors(
                $send(new BulkCreateAudienceContactsData(
                    listId: $data->listId,
                    properties: $data->properties,
                    contacts: $contacts,
                    listIds: $data->listIds,
                    topics: $data->topics,
                    updateExisting: $data->updateExisting,
                )),
                $offset,
            ),
            self::mergeStoreResults(...),
            fn () => $send($data),
        );
    }

    /**
     * Attach every (contact × list) combination. More than 1000 contacts are
     * sent in chunks of 1000 and the counts summed.
     *
     * @throws PartialBulkFailureException when a chunk fails after earlier chunks were saved.
     */
    public function bulkAttachLists(BulkAttachContactsToListsData $data): BulkAttachContactsToListsResult
    {
        $send = function (BulkAttachContactsToListsData $chunk): BulkAttachContactsToListsResult {
            /**
             * @var array{attached: int, already_attached: int, total_pairs: int} $response
             */
            $response = $this->transporter->post(self::ENDPOINT.'/lists/bulk', $chunk->toArray());

            return BulkAttachContactsToListsResult::from($response);
        };

        return $this->inChunks(
            $data->contactIds,
            fn (array $ids): BulkAttachContactsToListsResult => $send(new BulkAttachContactsToListsData($ids, $data->listIds)),
            static fn (BulkAttachContactsToListsResult $a, BulkAttachContactsToListsResult $b): BulkAttachContactsToListsResult => new BulkAttachContactsToListsResult(
                attached: $a->attached + $b->attached,
                alreadyAttached: $a->alreadyAttached + $b->alreadyAttached,
                totalPairs: $a->totalPairs + $b->totalPairs,
            ),
            fn () => $send($data),
        );
    }

    /**
     * Detach every (contact × list) combination. More than 1000 contacts are
     * sent in chunks of 1000 and the counts summed.
     *
     * @throws PartialBulkFailureException when a chunk fails after earlier chunks were saved.
     */
    public function bulkDetachLists(BulkDetachContactsFromListsData $data): BulkDetachContactsFromListsResult
    {
        $send = function (BulkDetachContactsFromListsData $chunk): BulkDetachContactsFromListsResult {
            /**
             * @var array{detached: int, not_present: int, total_pairs: int} $response
             */
            $response = $this->transporter->deleteWithBody(self::ENDPOINT.'/lists/bulk', $chunk->toArray());

            return BulkDetachContactsFromListsResult::from($response);
        };

        return $this->inChunks(
            $data->contactIds,
            fn (array $ids): BulkDetachContactsFromListsResult => $send(new BulkDetachContactsFromListsData($ids, $data->listIds)),
            static fn (BulkDetachContactsFromListsResult $a, BulkDetachContactsFromListsResult $b): BulkDetachContactsFromListsResult => new BulkDetachContactsFromListsResult(
                detached: $a->detached + $b->detached,
                notPresent: $a->notPresent + $b->notPresent,
                totalPairs: $a->totalPairs + $b->totalPairs,
            ),
            fn () => $send($data),
        );
    }

    /**
     * Subscribe every (contact × topic) combination (up to 50 topics). More
     * than 1000 contacts are sent in chunks of 1000 and the counts summed.
     *
     * @throws PartialBulkFailureException when a chunk fails after earlier chunks were saved.
     */
    public function bulkSubscribeTopics(BulkAudienceContactTopicsData $data): BulkSubscribeContactsToTopicsResult
    {
        $send = function (BulkAudienceContactTopicsData $chunk): BulkSubscribeContactsToTopicsResult {
            /**
             * @var array{subscribed: int, already_subscribed: int, total_pairs: int} $response
             */
            $response = $this->transporter->post(self::ENDPOINT.'/topics/bulk', $chunk->toArray());

            return BulkSubscribeContactsToTopicsResult::from($response);
        };

        return $this->inChunks(
            $data->contactIds,
            fn (array $ids): BulkSubscribeContactsToTopicsResult => $send(new BulkAudienceContactTopicsData($ids, $data->topicIds)),
            static fn (BulkSubscribeContactsToTopicsResult $a, BulkSubscribeContactsToTopicsResult $b): BulkSubscribeContactsToTopicsResult => new BulkSubscribeContactsToTopicsResult(
                subscribed: $a->subscribed + $b->subscribed,
                alreadySubscribed: $a->alreadySubscribed + $b->alreadySubscribed,
                totalPairs: $a->totalPairs + $b->totalPairs,
            ),
            fn () => $send($data),
        );
    }

    /**
     * Unsubscribe every (contact × topic) combination. Pairs that do not exist
     * are ignored. More than 1000 contacts are sent in chunks of 1000 and the
     * counts summed.
     *
     * @throws PartialBulkFailureException when a chunk fails after earlier chunks were saved.
     */
    public function bulkUnsubscribeTopics(BulkAudienceContactTopicsData $data): BulkUnsubscribeContactsFromTopicsResult
    {
        $send = function (BulkAudienceContactTopicsData $chunk): BulkUnsubscribeContactsFromTopicsResult {
            /**
             * @var array{unsubscribed: int, total_pairs: int} $response
             */
            $response = $this->transporter->deleteWithBody(self::ENDPOINT.'/topics/bulk', $chunk->toArray());

            return BulkUnsubscribeContactsFromTopicsResult::from($response);
        };

        return $this->inChunks(
            $data->contactIds,
            fn (array $ids): BulkUnsubscribeContactsFromTopicsResult => $send(new BulkAudienceContactTopicsData($ids, $data->topicIds)),
            static fn (BulkUnsubscribeContactsFromTopicsResult $a, BulkUnsubscribeContactsFromTopicsResult $b): BulkUnsubscribeContactsFromTopicsResult => new BulkUnsubscribeContactsFromTopicsResult(
                unsubscribed: $a->unsubscribed + $b->unsubscribed,
                totalPairs: $a->totalPairs + $b->totalPairs,
            ),
            fn () => $send($data),
        );
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

    private function sendBulkCreate(BulkCreateAudienceContactsData $data): BulkStoreAudienceContactsResult
    {
        /**
         * @var array{
         *     created: int,
         *     already_existed: int,
         *     updated?: int,
         *     error_count?: int,
         *     errors?: array<int, array{index: int, email: string|null, error_code: string, error: string}>,
         *     contacts?: array<int, array{id: string, email: string, created: bool}>,
         * } $response
         */
        $response = $this->transporter->post(self::ENDPOINT.'/bulk', $data->toArray());

        return BulkStoreAudienceContactsResult::from($response);
    }

    /**
     * Send `$items` in chunks of {@see self::BULK_CHUNK_SIZE} and fold the
     * results together. At or under the cap, `$single` sends the original
     * request untouched, so small calls are byte-identical to before.
     *
     * @template TItem
     * @template TResult of BulkStoreAudienceContactsResult|BulkAttachContactsToListsResult|BulkDetachContactsFromListsResult|BulkSubscribeContactsToTopicsResult|BulkUnsubscribeContactsFromTopicsResult
     *
     * @param  array<int, TItem>  $items
     * @param  callable(array<int, TItem>, int): TResult  $sendChunk  Receives the chunk and its offset in `$items`.
     * @param  callable(TResult, TResult): TResult  $merge
     * @param  callable(): TResult  $single
     * @return TResult
     *
     * @throws PartialBulkFailureException
     */
    private function inChunks(array $items, callable $sendChunk, callable $merge, callable $single): mixed
    {
        $total = count($items);

        if ($total <= self::BULK_CHUNK_SIZE) {
            return $single();
        }

        $result = null;
        $processed = 0;

        foreach (array_chunk(array_values($items), self::BULK_CHUNK_SIZE) as $chunk) {
            try {
                $chunkResult = $sendChunk($chunk, $processed);
            } catch (LettrException $e) {
                if ($result === null) {
                    throw $e;
                }

                throw new PartialBulkFailureException($result, $processed, $total, $e);
            }

            $result = $result === null ? $chunkResult : $merge($result, $chunkResult);
            $processed += count($chunk);
        }

        /** @var TResult $result */
        return $result;
    }

    /**
     * Shift a chunk's error indices so they point into the caller's input.
     */
    private static function offsetErrors(BulkStoreAudienceContactsResult $result, int $offset): BulkStoreAudienceContactsResult
    {
        if ($offset === 0 || $result->errors === []) {
            return $result;
        }

        return new BulkStoreAudienceContactsResult(
            created: $result->created,
            alreadyExisted: $result->alreadyExisted,
            updated: $result->updated,
            errorCount: $result->errorCount,
            errors: array_map(
                static fn (BulkAudienceContactError $error): BulkAudienceContactError => new BulkAudienceContactError(
                    index: $error->index + $offset,
                    email: $error->email,
                    errorCode: $error->errorCode,
                    error: $error->error,
                ),
                $result->errors,
            ),
            contacts: $result->contacts,
        );
    }

    private static function mergeStoreResults(BulkStoreAudienceContactsResult $a, BulkStoreAudienceContactsResult $b): BulkStoreAudienceContactsResult
    {
        return new BulkStoreAudienceContactsResult(
            created: $a->created + $b->created,
            alreadyExisted: $a->alreadyExisted + $b->alreadyExisted,
            updated: $a->updated + $b->updated,
            errorCount: $a->errorCount + $b->errorCount,
            errors: [...$a->errors, ...$b->errors],
            contacts: [...$a->contacts, ...$b->contacts],
        );
    }
}
