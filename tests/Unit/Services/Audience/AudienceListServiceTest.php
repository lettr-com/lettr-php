<?php

declare(strict_types=1);

use Lettr\Dto\Audience\AudienceList;
use Lettr\Dto\Audience\BulkDeleteAudienceListsData;
use Lettr\Dto\Audience\BulkDestroyAudienceListsResult;
use Lettr\Dto\Audience\CreateAudienceListData;
use Lettr\Dto\Audience\ListAudienceListsFilter;
use Lettr\Dto\Audience\UpdateAudienceListData;
use Lettr\Responses\ListAudienceListsResponse;
use Lettr\Services\Audience\AudienceListService;
use Tests\Support\MockTransporter;

test('list GETs audience/lists and returns ListAudienceListsResponse', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'lists' => [
            ['id' => '11111111-1111-1111-1111-111111111111', 'name' => 'Newsletter', 'contacts_count' => 42],
            ['id' => '22222222-2222-2222-2222-222222222222', 'name' => 'VIP', 'contacts_count' => 7],
        ],
        'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 2],
    ];

    $service = new AudienceListService($transporter);
    $response = $service->list();

    expect($transporter->lastUri)->toBe('audience/lists')
        ->and($transporter->lastQuery)->toBe([])
        ->and($response)->toBeInstanceOf(ListAudienceListsResponse::class)
        ->and($response->lists->count())->toBe(2)
        ->and($response->lists->all()[0]->name)->toBe('Newsletter')
        ->and($response->lists->all()[0]->contactsCount)->toBe(42)
        ->and($response->pagination->total)->toBe(2)
        ->and($response->hasMore())->toBeFalse();
});

test('list forwards filter query', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'lists' => [],
        'pagination' => ['current_page' => 2, 'last_page' => 3, 'per_page' => 5, 'total' => 15],
    ];

    $service = new AudienceListService($transporter);
    $service->list(ListAudienceListsFilter::create()->page(2)->perPage(5));

    expect($transporter->lastQuery)->toBe(['page' => 2, 'per_page' => 5]);
});

test('get GETs audience/lists/{id} and returns AudienceList', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['id' => 'abc', 'name' => 'My List', 'contacts_count' => 10];

    $service = new AudienceListService($transporter);
    $list = $service->get('abc');

    expect($transporter->lastUri)->toBe('audience/lists/abc')
        ->and($list)->toBeInstanceOf(AudienceList::class)
        ->and($list->id)->toBe('abc')
        ->and($list->name)->toBe('My List')
        ->and($list->contactsCount)->toBe(10);
});

test('create POSTs audience/lists with body', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['id' => 'new-id', 'name' => 'Fresh', 'contacts_count' => 0];

    $service = new AudienceListService($transporter);
    $list = $service->create(new CreateAudienceListData(name: 'Fresh'));

    expect($transporter->lastUri)->toBe('audience/lists')
        ->and($transporter->lastData)->toBe(['name' => 'Fresh'])
        ->and($list->id)->toBe('new-id');
});

test('update PATCHes audience/lists/{id} and only sends non-null fields', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['id' => 'abc', 'name' => 'Renamed', 'contacts_count' => 5];

    $service = new AudienceListService($transporter);
    $list = $service->update('abc', new UpdateAudienceListData(name: 'Renamed'));

    expect($transporter->lastUri)->toBe('audience/lists/abc')
        ->and($transporter->lastData)->toBe(['name' => 'Renamed'])
        ->and($list->name)->toBe('Renamed');
});

test('update with empty UpdateAudienceListData sends empty body', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['id' => 'abc', 'name' => 'Unchanged', 'contacts_count' => 5];

    $service = new AudienceListService($transporter);
    $service->update('abc', new UpdateAudienceListData);

    expect($transporter->lastData)->toBe([]);
});

test('delete hits DELETE audience/lists/{id}', function (): void {
    $transporter = new MockTransporter;

    $service = new AudienceListService($transporter);
    $service->delete('abc');

    expect($transporter->lastUri)->toBe('audience/lists/abc');
});

test('bulkDelete sends DELETE-with-body to audience/lists/bulk', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['deleted' => 3];

    $service = new AudienceListService($transporter);
    $result = $service->bulkDelete(new BulkDeleteAudienceListsData(['a', 'b', 'c']));

    expect($transporter->lastUri)->toBe('audience/lists/bulk')
        ->and($transporter->lastData)->toBe(['list_ids' => ['a', 'b', 'c']])
        ->and($result)->toBeInstanceOf(BulkDestroyAudienceListsResult::class)
        ->and($result->deleted)->toBe(3);
});
