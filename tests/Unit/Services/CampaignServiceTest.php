<?php

declare(strict_types=1);

use Lettr\Dto\Campaign\CampaignDetail;
use Lettr\Dto\Campaign\CampaignSummary;
use Lettr\Dto\Campaign\ListCampaignEventsFilter;
use Lettr\Dto\Campaign\ListCampaignsFilter;
use Lettr\Enums\CampaignStatus;
use Lettr\Enums\EventType;
use Lettr\Responses\ListCampaignEventsResponse;
use Lettr\Responses\ListCampaignsResponse;
use Lettr\Services\CampaignService;
use Tests\Support\MockTransporter;

/**
 * @return array<string, int>
 */
function campaignStatsFixture(): array
{
    return [
        'injections' => 130,
        'deliveries' => 124,
        'bounces' => 6,
        'spam_complaints' => 1,
        'opens' => 80,
        'unique_opens' => 60,
        'clicks' => 30,
        'unique_clicks' => 25,
        'unsubscribes' => 2,
    ];
}

/**
 * @return array<string, mixed>
 */
function campaignSummaryFixture(string $id = '0193e6a8-1f3a-7c2a-b9e2-1aa1d2e5d3f0'): array
{
    return [
        'id' => $id,
        'name' => 'Spring Sale',
        'subject' => 'Big news',
        'from_email' => 'hello@example.com',
        'from_name' => 'Example',
        'reply_to' => null,
        'status' => 'sent',
        'scheduled_at' => null,
        'total_recipients' => 130,
        'sent_count' => 124,
        'sent_at' => '2026-05-01T10:00:00+00:00',
        'created_at' => '2026-05-01T09:00:00+00:00',
        'stats' => campaignStatsFixture(),
    ];
}

test('list GETs campaigns and returns ListCampaignsResponse', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'campaigns' => [campaignSummaryFixture()],
        'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
    ];

    $service = new CampaignService($transporter);
    $response = $service->list();

    expect($transporter->lastUri)->toBe('campaigns')
        ->and($transporter->lastQuery)->toBe([])
        ->and($response)->toBeInstanceOf(ListCampaignsResponse::class)
        ->and($response->campaigns->count())->toBe(1)
        ->and($response->campaigns->all()[0])->toBeInstanceOf(CampaignSummary::class)
        ->and($response->campaigns->all()[0]->name)->toBe('Spring Sale')
        ->and($response->campaigns->all()[0]->status)->toBe(CampaignStatus::Sent)
        ->and($response->campaigns->all()[0]->sentCount)->toBe(124)
        ->and($response->campaigns->all()[0]->stats->uniqueOpens)->toBe(60)
        ->and($response->pagination->total)->toBe(1)
        ->and($response->hasMore())->toBeFalse();
});

test('list preserves unknown status as raw string instead of crashing', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'campaigns' => [[...campaignSummaryFixture(), 'status' => 'archived']],
        'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
    ];

    $service = new CampaignService($transporter);
    $response = $service->list();

    expect($response->campaigns->all()[0]->status)->toBe('archived');
});

test('list forwards filter query with status enum value', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'campaigns' => [],
        'pagination' => ['current_page' => 2, 'last_page' => 5, 'per_page' => 10, 'total' => 42],
    ];

    $service = new CampaignService($transporter);
    $response = $service->list(
        ListCampaignsFilter::create()->page(2)->perPage(10)->status(CampaignStatus::Scheduled),
    );

    expect($transporter->lastQuery)->toBe(['page' => 2, 'per_page' => 10, 'status' => 'scheduled'])
        ->and($response->hasMore())->toBeTrue();
});

test('get GETs campaigns/{id} and returns a CampaignDetail with htmlContent', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [...campaignSummaryFixture('abc'), 'html_content' => '<h1>Hi</h1>'];

    $service = new CampaignService($transporter);
    $campaign = $service->get('abc');

    expect($transporter->lastUri)->toBe('campaigns/abc')
        ->and($campaign)->toBeInstanceOf(CampaignDetail::class)
        ->and($campaign)->toBeInstanceOf(CampaignSummary::class)
        ->and($campaign->id)->toBe('abc')
        ->and($campaign->status)->toBe(CampaignStatus::Sent)
        ->and($campaign->htmlContent)->toBe('<h1>Hi</h1>');
});

test('get treats missing html_content as null', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = campaignSummaryFixture('abc');

    $service = new CampaignService($transporter);
    $campaign = $service->get('abc');

    expect($campaign)->toBeInstanceOf(CampaignDetail::class)
        ->and($campaign->htmlContent)->toBeNull();
});

test('events GETs campaigns/{id}/events and maps cursor', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'events' => [
            [
                'event_id' => '92356829',
                'event_type' => 'open',
                'email' => 'jane@example.com',
                'timestamp' => '2026-05-01T12:30:00+00:00',
            ],
        ],
        'next_cursor' => 'cursor-abc',
    ];

    $service = new CampaignService($transporter);
    $response = $service->events('abc');

    expect($transporter->lastUri)->toBe('campaigns/abc/events')
        ->and($transporter->lastQuery)->toBe([])
        ->and($response)->toBeInstanceOf(ListCampaignEventsResponse::class)
        ->and($response->events)->toHaveCount(1)
        ->and($response->events[0]->eventType)->toBe(EventType::Open)
        ->and($response->events[0]->email)->toBe('jane@example.com')
        ->and($response->nextCursor)->toBe('cursor-abc')
        ->and($response->hasMore())->toBeTrue();
});

test('events preserves unknown event_type as raw string', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'events' => [[
            'event_id' => '1',
            'event_type' => 'conversion',
            'email' => 'jane@example.com',
            'timestamp' => '2026-05-01T12:30:00+00:00',
        ]],
        'next_cursor' => null,
    ];

    $service = new CampaignService($transporter);
    $response = $service->events('abc');

    expect($response->events[0]->eventType)->toBe('conversion');
});

test('events with no next_cursor reports no more pages', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['events' => [], 'next_cursor' => null];

    $service = new CampaignService($transporter);
    $response = $service->events('abc');

    expect($response->events)->toBe([])
        ->and($response->nextCursor)->toBeNull()
        ->and($response->hasMore())->toBeFalse();
});

test('events forwards filter query', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['events' => [], 'next_cursor' => null];

    $service = new CampaignService($transporter);
    $service->events('abc', ListCampaignEventsFilter::create()
        ->eventType(EventType::Click)
        ->email('jane@example.com')
        ->startDate(new DateTimeImmutable('2026-05-01T00:00:00+00:00'))
        ->endDate('2026-05-31T23:59:59+00:00')
        ->limit(50)
        ->cursor('next-page'));

    expect($transporter->lastQuery)->toBe([
        'event_type' => 'click',
        'email' => 'jane@example.com',
        'start_date' => '2026-05-01T00:00:00+00:00',
        'end_date' => '2026-05-31T23:59:59+00:00',
        'limit' => 50,
        'cursor' => 'next-page',
    ]);
});

test('events filter drops null and empty cursor from query', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['events' => [], 'next_cursor' => null];

    $service = new CampaignService($transporter);
    $service->events('abc', ListCampaignEventsFilter::create()->cursor(null));

    expect($transporter->lastQuery)->toBe([]);

    $service->events('abc', ListCampaignEventsFilter::create()->cursor(''));

    expect($transporter->lastQuery)->toBe([]);
});

test('send POSTs to campaigns/{id}/send with no request body and returns the campaign', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['message' => 'Campaign sent.', 'data' => campaignSummaryFixture('abc')];

    $service = new CampaignService($transporter);
    $campaign = $service->send('abc');

    expect($transporter->lastUri)->toBe('campaigns/abc/send')
        ->and($transporter->lastData)->toBeNull()
        ->and($campaign)->toBeInstanceOf(CampaignSummary::class)
        ->and($campaign->id)->toBe('abc');
});

test('schedule POSTs to campaigns/{id}/schedule with ISO-8601 scheduled_at from DateTime', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['message' => 'Scheduled.', 'data' => campaignSummaryFixture('abc')];

    $service = new CampaignService($transporter);
    $campaign = $service->schedule('abc', new DateTimeImmutable('2026-06-01T09:00:00+00:00'));

    expect($transporter->lastUri)->toBe('campaigns/abc/schedule')
        ->and($transporter->lastData)->toBe(['scheduled_at' => '2026-06-01T09:00:00+00:00'])
        ->and($campaign)->toBeInstanceOf(CampaignSummary::class);
});

test('schedule passes a string scheduled_at through unchanged', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['message' => 'Scheduled.', 'data' => campaignSummaryFixture('abc')];

    $service = new CampaignService($transporter);
    $service->schedule('abc', '2026-06-01T11:00:00+02:00');

    expect($transporter->lastData)->toBe(['scheduled_at' => '2026-06-01T11:00:00+02:00']);
});

test('unschedule POSTs to campaigns/{id}/unschedule with no body', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['message' => 'Unscheduled.', 'data' => campaignSummaryFixture('abc')];

    $service = new CampaignService($transporter);
    $campaign = $service->unschedule('abc');

    expect($transporter->lastUri)->toBe('campaigns/abc/unschedule')
        ->and($transporter->lastData)->toBeNull()
        ->and($campaign)->toBeInstanceOf(CampaignSummary::class);
});

test('action method refetches campaign via GET when envelope omits data', function (): void {
    $transporter = new MockTransporter;
    // No 'data' key — simulates the rare quirk. The same response is also
    // returned for the follow-up GET (it's already a full campaign payload),
    // so the service ends up with a real CampaignSummary.
    $transporter->response = campaignSummaryFixture('abc');

    $service = new CampaignService($transporter);
    $campaign = $service->send('abc');

    expect($campaign)->toBeInstanceOf(CampaignSummary::class)
        ->and($campaign->id)->toBe('abc')
        // Last call was the refetch.
        ->and($transporter->lastUri)->toBe('campaigns/abc');
});

test('action methods return CampaignSummary without htmlContent even when refetch picks up html_content', function (): void {
    $transporter = new MockTransporter;
    // Envelope omits `data`, forcing the refetch. The GET response happens
    // to include `html_content`, but action methods must NOT expose it.
    $transporter->response = [...campaignSummaryFixture('abc'), 'html_content' => '<h1>leak</h1>'];

    $service = new CampaignService($transporter);
    $campaign = $service->send('abc');

    expect($campaign)->toBeInstanceOf(CampaignSummary::class)
        ->and($campaign)->not->toBeInstanceOf(CampaignDetail::class)
        ->and($campaign->id)->toBe('abc');
});
