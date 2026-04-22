<?php

declare(strict_types=1);

use Lettr\Collections\WebhookCollection;
use Lettr\Dto\Webhook\CreateWebhookData;
use Lettr\Dto\Webhook\UpdateWebhookData;
use Lettr\Dto\Webhook\Webhook;
use Lettr\Enums\WebhookAuthType;
use Lettr\Enums\WebhookEventsMode;
use Lettr\Enums\WebhookEventType;
use Lettr\Services\WebhookService;
use Tests\Support\MockTransporter;

test('list returns WebhookCollection with prefixed event types', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'webhooks' => [
            [
                'id' => 'wh_1',
                'name' => 'Order notifications',
                'url' => 'https://example.com/hook',
                'enabled' => true,
                'auth_type' => 'basic',
                'has_auth_credentials' => true,
                'event_types' => ['message.delivery', 'message.bounce', 'engagement.click'],
                'last_status' => 'success',
                'last_successful_at' => '2026-04-17T10:00:00+00:00',
                'last_failure_at' => null,
            ],
        ],
    ];

    $service = new WebhookService($transporter);
    $webhooks = $service->list();

    expect($transporter->lastUri)->toBe('webhooks')
        ->and($webhooks)->toBeInstanceOf(WebhookCollection::class)
        ->and($webhooks->count())->toBe(1);

    $wh = $webhooks->all()[0];
    expect($wh)->toBeInstanceOf(Webhook::class)
        ->and($wh->name)->toBe('Order notifications')
        ->and($wh->authType)->toBe(WebhookAuthType::Basic)
        ->and($wh->eventTypes->count())->toBe(3)
        ->and($wh->eventTypes->contains(WebhookEventType::MessageDelivery))->toBeTrue()
        ->and($wh->eventTypes->contains(WebhookEventType::EngagementClick))->toBeTrue()
        ->and($wh->listensTo(WebhookEventType::MessageDelivery))->toBeTrue()
        ->and($wh->isHealthy())->toBeTrue();
});

test('list keeps null event_types as null to signal all-events subscription', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'webhooks' => [
            [
                'id' => 'wh_all',
                'name' => 'All events',
                'url' => 'https://example.com/hook',
                'enabled' => true,
                'auth_type' => 'none',
                'has_auth_credentials' => false,
                'event_types' => null,
            ],
        ],
    ];

    $service = new WebhookService($transporter);
    $wh = $service->list()->all()[0];

    expect($wh->eventTypes)->toBeNull()
        ->and($wh->listensToAllEvents())->toBeTrue()
        ->and($wh->listensTo(WebhookEventType::MessageDelivery))->toBeTrue();
});

test('get returns Webhook by id', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'id' => 'wh_42',
        'name' => 'Single',
        'url' => 'https://example.com/hook',
        'enabled' => false,
        'auth_type' => 'oauth2',
        'has_auth_credentials' => true,
        'event_types' => ['unsubscribe.list_unsubscribe'],
        'last_status' => 'failure',
        'last_failure_at' => '2026-04-18T08:00:00+00:00',
    ];

    $service = new WebhookService($transporter);
    $wh = $service->get('wh_42');

    expect($transporter->lastUri)->toBe('webhooks/wh_42')
        ->and($wh->authType)->toBe(WebhookAuthType::OAuth2)
        ->and($wh->enabled)->toBeFalse()
        ->and($wh->isFailing())->toBeTrue()
        ->and($wh->eventTypes->contains(WebhookEventType::UnsubscribeList))->toBeTrue();
});

test('create POST webhooks with typed events', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'id' => 'wh_new',
        'name' => 'Typed',
        'url' => 'https://example.com/hook',
        'enabled' => true,
        'auth_type' => 'basic',
        'has_auth_credentials' => true,
        'event_types' => ['message.delivery', 'message.bounce'],
    ];

    $service = new WebhookService($transporter);
    $data = new CreateWebhookData(
        name: 'Typed',
        url: 'https://example.com/hook',
        authType: WebhookAuthType::Basic,
        eventsMode: WebhookEventsMode::Selected,
        authUsername: 'alice',
        authPassword: 'hunter2',
        events: [WebhookEventType::MessageDelivery, WebhookEventType::MessageBounce],
    );
    $wh = $service->create($data);

    expect($transporter->lastUri)->toBe('webhooks')
        ->and($transporter->lastData)->toBe([
            'name' => 'Typed',
            'url' => 'https://example.com/hook',
            'auth_type' => 'basic',
            'events_mode' => 'selected',
            'auth_username' => 'alice',
            'auth_password' => 'hunter2',
            'events' => ['message.delivery', 'message.bounce'],
        ])
        ->and($wh->id->value)->toBe('wh_new');
});

test('create accepts plain string events for flexibility', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'id' => 'wh_raw',
        'name' => 'Raw',
        'url' => 'https://example.com/hook',
        'enabled' => true,
        'auth_type' => 'none',
        'has_auth_credentials' => false,
        'event_types' => ['message.delivery'],
    ];

    $service = new WebhookService($transporter);
    $data = new CreateWebhookData(
        name: 'Raw',
        url: 'https://example.com/hook',
        authType: WebhookAuthType::None,
        eventsMode: WebhookEventsMode::Selected,
        events: ['message.delivery'],
    );
    $service->create($data);

    expect($transporter->lastData['events'])->toBe(['message.delivery']);
});

test('update PUT webhooks/{id} emits url field', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'id' => 'wh_upd',
        'name' => 'Updated',
        'url' => 'https://example.com/new',
        'enabled' => true,
        'auth_type' => 'none',
        'has_auth_credentials' => false,
        'event_types' => ['engagement.amp_click'],
    ];

    $service = new WebhookService($transporter);
    $service->update('wh_upd', new UpdateWebhookData(
        name: 'Updated',
        url: 'https://example.com/new',
        events: [WebhookEventType::EngagementAmpClick],
        active: true,
    ));

    expect($transporter->lastUri)->toBe('webhooks/wh_upd')
        ->and($transporter->lastData)->toBe([
            'name' => 'Updated',
            'url' => 'https://example.com/new',
            'events' => ['engagement.amp_click'],
            'active' => true,
        ]);
});

test('delete hits DELETE /webhooks/{id}', function (): void {
    $transporter = new MockTransporter;
    $service = new WebhookService($transporter);

    $service->delete('wh_gone');

    expect($transporter->lastUri)->toBe('webhooks/wh_gone');
});

test('WebhookEventType categorisation helpers', function (): void {
    expect(WebhookEventType::MessageDelivery->isMessage())->toBeTrue()
        ->and(WebhookEventType::MessageDelivery->isEngagement())->toBeFalse()
        ->and(WebhookEventType::EngagementClick->isEngagement())->toBeTrue()
        ->and(WebhookEventType::EngagementAmpOpen->isEngagement())->toBeTrue()
        ->and(WebhookEventType::UnsubscribeLink->category())->toBe('unsubscribe');
});
