<?php

declare(strict_types=1);

use Lettr\Dto\Audience\BulkAttachContactsToListsData;
use Lettr\Dto\Audience\BulkAttachContactsToListsResult;
use Lettr\Dto\Audience\BulkAudienceContactRow;
use Lettr\Dto\Audience\BulkAudienceContactTopicsData;
use Lettr\Dto\Audience\BulkCreateAudienceContactsData;
use Lettr\Dto\Audience\BulkDetachContactsFromListsData;
use Lettr\Exceptions\PartialBulkFailureException;
use Lettr\Exceptions\RateLimitException;
use Lettr\Exceptions\ValidationException;
use Lettr\Services\Audience\AudienceContactService;
use Tests\Support\SequenceTransporter;

/**
 * @return array<int, string>
 */
function chunkEmails(int $count): array
{
    return array_map(static fn (int $i): string => "user{$i}@example.com", range(0, $count - 1));
}

/**
 * @return array<int, string>
 */
function chunkIds(int $count): array
{
    return array_map(static fn (int $i): string => "c-{$i}", range(0, $count - 1));
}

/**
 * A bulk-create response for `$count` rows, reporting an error on the
 * chunk-local row `$errorAt` when given.
 *
 * @return array<string, mixed>
 */
function storeResponse(int $count, ?int $errorAt = null): array
{
    return [
        'created' => $count - ($errorAt === null ? 0 : 1),
        'already_existed' => 0,
        'updated' => 0,
        'error_count' => $errorAt === null ? 0 : 1,
        'errors' => $errorAt === null ? [] : [[
            'index' => $errorAt,
            'email' => 'bad',
            'error_code' => 'invalid_email',
            'error' => 'Invalid email.',
        ]],
        'contacts' => [['id' => 'id-'.$count, 'email' => 'x@example.com', 'created' => true]],
    ];
}

test('2500 emails go out as three requests of 1000, 1000 and 500', function (): void {
    $transporter = new SequenceTransporter([storeResponse(1000), storeResponse(1000), storeResponse(500)]);
    $service = new AudienceContactService($transporter);

    $result = $service->bulkCreate(BulkCreateAudienceContactsData::forEmails(
        chunkEmails(2500),
        listId: 'list-1',
        properties: ['source' => 'import'],
        updateExisting: true,
    ));

    expect($transporter->calls)->toHaveCount(3)
        ->and(array_map(static fn (array $call): int => count($call['data']['emails']), $transporter->calls))->toBe([1000, 1000, 500])
        ->and($transporter->calls[1]['data']['emails'][0])->toBe('user1000@example.com')
        // Batch-wide settings travel with every chunk.
        ->and($transporter->calls[2]['data']['list_id'])->toBe('list-1')
        ->and($transporter->calls[2]['data']['properties'])->toBe(['source' => 'import'])
        ->and($transporter->calls[2]['data']['update_existing'])->toBeTrue()
        ->and($result->created)->toBe(2500)
        ->and($result->contacts)->toHaveCount(3);
});

test('error indices point into the caller\'s input, not the chunk', function (): void {
    $transporter = new SequenceTransporter([storeResponse(1000, errorAt: 5), storeResponse(1000, errorAt: 7)]);
    $service = new AudienceContactService($transporter);

    $result = $service->bulkCreate(BulkCreateAudienceContactsData::forEmails(chunkEmails(2000)));

    expect($result->errorCount)->toBe(2)
        ->and(array_map(static fn ($e): int => $e->index, $result->errors))->toBe([5, 1007])
        ->and($result->hasErrors())->toBeTrue();
});

test('per-contact rows are chunked too', function (): void {
    $rows = array_map(static fn (string $email): BulkAudienceContactRow => new BulkAudienceContactRow($email), chunkEmails(1001));
    $transporter = new SequenceTransporter([storeResponse(1000), storeResponse(1)]);
    $service = new AudienceContactService($transporter);

    $service->bulkCreate(BulkCreateAudienceContactsData::forContacts($rows, listIds: ['l-1']));

    expect($transporter->calls)->toHaveCount(2)
        ->and($transporter->calls[1]['data']['contacts'])->toHaveCount(1)
        ->and($transporter->calls[1]['data']['list_ids'])->toBe(['l-1'])
        ->and($transporter->calls[1]['data'])->not->toHaveKey('emails');
});

test('exactly 1000 items is a single request with the original payload', function (): void {
    $data = BulkCreateAudienceContactsData::forEmails(chunkEmails(1000), listId: 'list-1');
    $transporter = new SequenceTransporter([storeResponse(1000)]);

    (new AudienceContactService($transporter))->bulkCreate($data);

    expect($transporter->calls)->toHaveCount(1)
        ->and($transporter->calls[0]['data'])->toBe($data->toArray());
});

test('list attach, list detach and topic calls chunk contact ids and sum counts', function (): void {
    $transporter = new SequenceTransporter([
        ['attached' => 1000, 'already_attached' => 0, 'total_pairs' => 1000],
        ['attached' => 4, 'already_attached' => 1, 'total_pairs' => 5],
        ['detached' => 1000, 'not_present' => 0, 'total_pairs' => 1000],
        ['detached' => 3, 'not_present' => 2, 'total_pairs' => 5],
        ['subscribed' => 1000, 'already_subscribed' => 0, 'total_pairs' => 1000],
        ['subscribed' => 5, 'already_subscribed' => 0, 'total_pairs' => 5],
        ['unsubscribed' => 1000, 'total_pairs' => 1000],
        ['unsubscribed' => 5, 'total_pairs' => 5],
    ]);
    $service = new AudienceContactService($transporter);
    $ids = chunkIds(1005);

    $attached = $service->bulkAttachLists(new BulkAttachContactsToListsData($ids, ['l-1']));
    $detached = $service->bulkDetachLists(new BulkDetachContactsFromListsData($ids, ['l-1']));
    $subscribed = $service->bulkSubscribeTopics(new BulkAudienceContactTopicsData($ids, ['t-1']));
    $unsubscribed = $service->bulkUnsubscribeTopics(new BulkAudienceContactTopicsData($ids, ['t-1']));

    expect($transporter->calls)->toHaveCount(8)
        ->and($transporter->calls[1]['data'])->toBe(['contact_ids' => array_slice($ids, 1000), 'list_ids' => ['l-1']])
        ->and($transporter->calls[3]['method'])->toBe('DELETE')
        ->and($transporter->calls[7]['data']['topic_ids'])->toBe(['t-1'])
        ->and([$attached->attached, $attached->alreadyAttached, $attached->totalPairs])->toBe([1004, 1, 1005])
        ->and([$detached->detached, $detached->notPresent, $detached->totalPairs])->toBe([1003, 2, 1005])
        ->and([$subscribed->subscribed, $subscribed->totalPairs])->toBe([1005, 1005])
        ->and([$unsubscribed->unsubscribed, $unsubscribed->totalPairs])->toBe([1005, 1005]);
});

test('a failure after a saved chunk throws PartialBulkFailureException with what was saved', function (): void {
    $rateLimited = new RateLimitException('Too many requests.');
    $transporter = new SequenceTransporter([storeResponse(1000, errorAt: 3), $rateLimited]);
    $service = new AudienceContactService($transporter);

    try {
        $service->bulkCreate(BulkCreateAudienceContactsData::forEmails(chunkEmails(2500)));
        $this->fail('Expected PartialBulkFailureException.');
    } catch (PartialBulkFailureException $e) {
        expect($e->processed)->toBe(1000)
            ->and($e->total)->toBe(2500)
            ->and($e->result->created)->toBe(999)
            ->and($e->getPrevious())->toBe($rateLimited)
            ->and($e->getMessage())->toContain('1000 of 2500');
    }

    // It stopped at the failing chunk rather than carrying on.
    expect($transporter->calls)->toHaveCount(2);
});

test('a failure in the first chunk rethrows the original exception unchanged', function (): void {
    $validation = new ValidationException('Invalid.', ['list_id' => ['Unknown list.']]);
    $transporter = new SequenceTransporter([$validation]);
    $service = new AudienceContactService($transporter);

    expect(fn () => $service->bulkAttachLists(new BulkAttachContactsToListsData(chunkIds(1500), ['nope'])))
        ->toThrow(fn (ValidationException $e) => expect($e)->toBe($validation));
});

test('the merged result type is unchanged', function (): void {
    $transporter = new SequenceTransporter([
        ['attached' => 1000, 'already_attached' => 0, 'total_pairs' => 1000],
        ['attached' => 1, 'already_attached' => 0, 'total_pairs' => 1],
    ]);

    $result = (new AudienceContactService($transporter))
        ->bulkAttachLists(new BulkAttachContactsToListsData(chunkIds(1001), ['l-1']));

    expect($result)->toBeInstanceOf(BulkAttachContactsToListsResult::class);
});

test('emails and contacts together are sent once, unsplit', function (): void {
    $data = new BulkCreateAudienceContactsData(
        emails: chunkEmails(1500),
        contacts: [new BulkAudienceContactRow('row@example.com')],
    );
    $transporter = new SequenceTransporter([storeResponse(1501)]);

    (new AudienceContactService($transporter))->bulkCreate($data);

    expect($transporter->calls)->toHaveCount(1)
        ->and($transporter->calls[0]['data'])->toBe($data->toArray());
});
