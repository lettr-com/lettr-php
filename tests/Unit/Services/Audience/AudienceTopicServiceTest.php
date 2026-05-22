<?php

declare(strict_types=1);

use Lettr\Dto\Audience\AudienceTopic;
use Lettr\Dto\Audience\CreateAudienceTopicData;
use Lettr\Dto\Audience\UpdateAudienceTopicData;
use Lettr\Enums\AudienceTopicDefaultSubscription;
use Lettr\Enums\AudienceTopicVisibility;
use Lettr\Services\Audience\AudienceTopicService;
use Tests\Support\MockTransporter;

function sampleTopicResponse(): array
{
    return [
        'id' => 't-1',
        'name' => 'Releases',
        'description' => 'Product release notes',
        'default_subscription' => 'opt_in',
        'visibility' => 'public',
        'contacts_count' => 12,
        'created_at' => '2026-01-01T00:00:00+00:00',
    ];
}

test('list GETs audience/topics', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'topics' => [sampleTopicResponse()],
        'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
    ];

    $service = new AudienceTopicService($transporter);
    $response = $service->list();

    $topic = $response->topics->all()[0];

    expect($transporter->lastUri)->toBe('audience/topics')
        ->and($topic->defaultSubscription)->toBe(AudienceTopicDefaultSubscription::OptIn)
        ->and($topic->visibility)->toBe(AudienceTopicVisibility::PublicVisibility);
});

test('get GETs audience/topics/{id}', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sampleTopicResponse();

    $service = new AudienceTopicService($transporter);
    $topic = $service->get('t-1');

    expect($transporter->lastUri)->toBe('audience/topics/t-1')
        ->and($topic)->toBeInstanceOf(AudienceTopic::class)
        ->and($topic->contactsCount)->toBe(12);
});

test('create POSTs audience/topics with enums serialized to strings', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sampleTopicResponse();

    $service = new AudienceTopicService($transporter);
    $service->create(new CreateAudienceTopicData(
        name: 'Releases',
        description: 'Product release notes',
        defaultSubscription: AudienceTopicDefaultSubscription::OptIn,
        visibility: AudienceTopicVisibility::PublicVisibility,
    ));

    expect($transporter->lastUri)->toBe('audience/topics')
        ->and($transporter->lastData)->toBe([
            'name' => 'Releases',
            'description' => 'Product release notes',
            'default_subscription' => 'opt_in',
            'visibility' => 'public',
        ]);
});

test('update PATCHes audience/topics/{id} and does not send default_subscription', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sampleTopicResponse();

    $service = new AudienceTopicService($transporter);
    $service->update('t-1', new UpdateAudienceTopicData(
        name: 'New name',
        visibility: AudienceTopicVisibility::PrivateVisibility,
    ));

    expect($transporter->lastUri)->toBe('audience/topics/t-1')
        ->and($transporter->lastData)->toBe([
            'name' => 'New name',
            'visibility' => 'private',
        ])
        ->and($transporter->lastData)->not->toHaveKey('default_subscription');
});

test('delete hits DELETE audience/topics/{id}', function (): void {
    $transporter = new MockTransporter;

    $service = new AudienceTopicService($transporter);
    $service->delete('t-1');

    expect($transporter->lastUri)->toBe('audience/topics/t-1');
});
