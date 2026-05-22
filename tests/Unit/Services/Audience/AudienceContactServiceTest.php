<?php

declare(strict_types=1);

use Lettr\Dto\Audience\AudienceContact;
use Lettr\Dto\Audience\BulkAttachContactsToListsData;
use Lettr\Dto\Audience\BulkAttachContactsToListsResult;
use Lettr\Dto\Audience\BulkCreateAudienceContactsData;
use Lettr\Dto\Audience\BulkDetachContactsFromListsData;
use Lettr\Dto\Audience\BulkDetachContactsFromListsResult;
use Lettr\Dto\Audience\BulkStoreAudienceContactsResult;
use Lettr\Dto\Audience\CreateAudienceContactData;
use Lettr\Dto\Audience\DoubleOptInConfig;
use Lettr\Dto\Audience\ListAudienceContactsFilter;
use Lettr\Dto\Audience\UpdateAudienceContactData;
use Lettr\Enums\AudienceContactStatus;
use Lettr\Responses\ListAudienceContactsResponse;
use Lettr\Services\Audience\AudienceContactService;
use Lettr\ValueObjects\ContactProperties;
use Tests\Support\MockTransporter;

function sampleContactResponse(): array
{
    return [
        'id' => 'c-1',
        'email' => 'alice@example.com',
        'status' => 'subscribed',
        'properties' => ['first_name' => 'Alice', 'plan' => 'pro'],
        'created_at' => '2026-01-01T00:00:00+00:00',
        'lists' => [['id' => 'l-1', 'name' => 'Newsletter']],
        'topics' => [['id' => 't-1', 'name' => 'Releases']],
    ];
}

test('list GETs audience/contacts and parses lists/topics links', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'contacts' => [sampleContactResponse()],
        'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
    ];

    $service = new AudienceContactService($transporter);
    $response = $service->list();

    $first = $response->contacts->all()[0];

    expect($transporter->lastUri)->toBe('audience/contacts')
        ->and($response)->toBeInstanceOf(ListAudienceContactsResponse::class)
        ->and($first)->toBeInstanceOf(AudienceContact::class)
        ->and($first->email)->toBe('alice@example.com')
        ->and($first->status)->toBe(AudienceContactStatus::Subscribed)
        ->and($first->properties)->toBeInstanceOf(ContactProperties::class)
        ->and($first->properties->get('first_name'))->toBe('Alice')
        ->and($first->lists[0]->name)->toBe('Newsletter')
        ->and($first->topics[0]->name)->toBe('Releases');
});

test('list forwards filter query including status enum and ids', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'contacts' => [],
        'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 0],
    ];

    $service = new AudienceContactService($transporter);
    $service->list(
        ListAudienceContactsFilter::create()
            ->search('alice')
            ->status(AudienceContactStatus::Subscribed)
            ->listId('l-1')
            ->segmentId('s-1')
            ->page(2)
            ->perPage(50),
    );

    expect($transporter->lastQuery)->toBe([
        'page' => 2,
        'per_page' => 50,
        'search' => 'alice',
        'status' => 'subscribed',
        'list_id' => 'l-1',
        'segment_id' => 's-1',
    ]);
});

test('get GETs audience/contacts/{id}', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sampleContactResponse();

    $service = new AudienceContactService($transporter);
    $contact = $service->get('c-1');

    expect($transporter->lastUri)->toBe('audience/contacts/c-1')
        ->and($contact->id)->toBe('c-1');
});

test('create POSTs audience/contacts with double_opt_in payload', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sampleContactResponse();

    $service = new AudienceContactService($transporter);
    $service->create(new CreateAudienceContactData(
        email: 'alice@example.com',
        listId: 'l-1',
        properties: ['first_name' => 'Alice'],
        doubleOptIn: new DoubleOptInConfig(
            from: 'team@example.com',
            subject: 'Confirm',
            templateSlug: 'confirm',
            redirectUrl: 'https://example.com/done',
            fromName: 'Team',
        ),
    ));

    expect($transporter->lastUri)->toBe('audience/contacts')
        ->and($transporter->lastData)->toBe([
            'email' => 'alice@example.com',
            'list_id' => 'l-1',
            'properties' => ['first_name' => 'Alice'],
            'double_opt_in' => [
                'from' => 'team@example.com',
                'subject' => 'Confirm',
                'template_slug' => 'confirm',
                'redirect_url' => 'https://example.com/done',
                'from_name' => 'Team',
            ],
        ]);
});

test('create omits optional fields when null', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sampleContactResponse();

    $service = new AudienceContactService($transporter);
    $service->create(new CreateAudienceContactData(email: 'a@b.com'));

    expect($transporter->lastData)->toBe(['email' => 'a@b.com']);
});

test('update PATCHes audience/contacts/{id} with status enum + nullable properties', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sampleContactResponse();

    $service = new AudienceContactService($transporter);
    $service->update('c-1', new UpdateAudienceContactData(
        email: 'new@example.com',
        status: AudienceContactStatus::Unsubscribed,
        properties: ['plan' => 'free', 'first_name' => null],
    ));

    expect($transporter->lastUri)->toBe('audience/contacts/c-1')
        ->and($transporter->lastData)->toBe([
            'email' => 'new@example.com',
            'status' => 'unsubscribed',
            'properties' => ['plan' => 'free', 'first_name' => null],
        ]);
});

test('delete hits DELETE audience/contacts/{id}', function (): void {
    $transporter = new MockTransporter;

    $service = new AudienceContactService($transporter);
    $service->delete('c-1');

    expect($transporter->lastUri)->toBe('audience/contacts/c-1');
});

test('bulkCreate POSTs audience/contacts/bulk and returns counts', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['created' => 7, 'already_existed' => 3];

    $service = new AudienceContactService($transporter);
    $result = $service->bulkCreate(new BulkCreateAudienceContactsData(
        emails: ['a@x.com', 'b@x.com'],
        listId: 'l-1',
        properties: ['plan' => 'pro'],
    ));

    expect($transporter->lastUri)->toBe('audience/contacts/bulk')
        ->and($transporter->lastData)->toBe([
            'emails' => ['a@x.com', 'b@x.com'],
            'list_id' => 'l-1',
            'properties' => ['plan' => 'pro'],
        ])
        ->and($result)->toBeInstanceOf(BulkStoreAudienceContactsResult::class)
        ->and($result->created)->toBe(7)
        ->and($result->alreadyExisted)->toBe(3);
});

test('bulkAttachLists POSTs audience/contacts/lists/bulk', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['attached' => 4, 'already_attached' => 2, 'total_pairs' => 6];

    $service = new AudienceContactService($transporter);
    $result = $service->bulkAttachLists(new BulkAttachContactsToListsData(
        contactIds: ['c-1', 'c-2'],
        listIds: ['l-1', 'l-2', 'l-3'],
    ));

    expect($transporter->lastUri)->toBe('audience/contacts/lists/bulk')
        ->and($transporter->lastData)->toBe([
            'contact_ids' => ['c-1', 'c-2'],
            'list_ids' => ['l-1', 'l-2', 'l-3'],
        ])
        ->and($result)->toBeInstanceOf(BulkAttachContactsToListsResult::class)
        ->and($result->totalPairs)->toBe(6);
});

test('bulkDetachLists sends DELETE-with-body to audience/contacts/lists/bulk', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['detached' => 1, 'not_present' => 1, 'total_pairs' => 2];

    $service = new AudienceContactService($transporter);
    $result = $service->bulkDetachLists(new BulkDetachContactsFromListsData(
        contactIds: ['c-1'],
        listIds: ['l-1', 'l-2'],
    ));

    expect($transporter->lastUri)->toBe('audience/contacts/lists/bulk')
        ->and($transporter->lastData)->toBe([
            'contact_ids' => ['c-1'],
            'list_ids' => ['l-1', 'l-2'],
        ])
        ->and($result)->toBeInstanceOf(BulkDetachContactsFromListsResult::class)
        ->and($result->detached)->toBe(1)
        ->and($result->notPresent)->toBe(1);
});

test('attachList returns true for HTTP 201 (newly attached)', function (): void {
    $transporter = new MockTransporter;
    $transporter->statusCode = 201;

    $service = new AudienceContactService($transporter);
    $result = $service->attachList('c-1', 'l-1');

    expect($transporter->lastUri)->toBe('audience/contacts/c-1/lists/l-1')
        ->and($transporter->lastData)->toBe([])
        ->and($result)->toBeTrue();
});

test('attachList returns false for HTTP 200 (already attached)', function (): void {
    $transporter = new MockTransporter;
    $transporter->statusCode = 200;

    $service = new AudienceContactService($transporter);

    expect($service->attachList('c-1', 'l-1'))->toBeFalse();
});

test('detachList hits DELETE audience/contacts/{c}/lists/{l}', function (): void {
    $transporter = new MockTransporter;

    $service = new AudienceContactService($transporter);
    $service->detachList('c-1', 'l-1');

    expect($transporter->lastUri)->toBe('audience/contacts/c-1/lists/l-1');
});

test('subscribeTopic returns true for HTTP 201', function (): void {
    $transporter = new MockTransporter;
    $transporter->statusCode = 201;

    $service = new AudienceContactService($transporter);
    $result = $service->subscribeTopic('c-1', 't-1');

    expect($transporter->lastUri)->toBe('audience/contacts/c-1/topics/t-1')
        ->and($result)->toBeTrue();
});

test('subscribeTopic returns false for HTTP 200', function (): void {
    $transporter = new MockTransporter;
    $transporter->statusCode = 200;

    $service = new AudienceContactService($transporter);

    expect($service->subscribeTopic('c-1', 't-1'))->toBeFalse();
});

test('unsubscribeTopic hits DELETE audience/contacts/{c}/topics/{t}', function (): void {
    $transporter = new MockTransporter;

    $service = new AudienceContactService($transporter);
    $service->unsubscribeTopic('c-1', 't-1');

    expect($transporter->lastUri)->toBe('audience/contacts/c-1/topics/t-1');
});
