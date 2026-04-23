<?php

declare(strict_types=1);

use Lettr\Builders\EmailBuilder;
use Lettr\Dto\Email\ListEmailEventsFilter;
use Lettr\Dto\Email\ListEmailsFilter;
use Lettr\Dto\Email\SendEmailData;
use Lettr\Dto\Email\SendEmailResponse;
use Lettr\Dto\Email\TransmissionDetail;
use Lettr\Dto\SendingQuota;
use Lettr\Enums\TransmissionState;
use Lettr\Responses\ListEmailEventsResponse;
use Lettr\Responses\ListEmailsResponse;
use Lettr\Services\EmailService;
use Lettr\ValueObjects\EmailAddress;
use Tests\Support\MockTransporter;

test('can create EmailService instance', function (): void {
    $transporter = new MockTransporter;
    $service = new EmailService($transporter);

    expect($service)->toBeInstanceOf(EmailService::class);
});

test('create returns EmailBuilder', function (): void {
    $transporter = new MockTransporter;
    $service = new EmailService($transporter);

    expect($service->create())->toBeInstanceOf(EmailBuilder::class);
});

test('send method calls transporter with correct data', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_123', 'accepted' => 1, 'rejected' => 0];

    $service = new EmailService($transporter);
    $data = SendEmailData::from([
        'from' => 'sender@example.com',
        'to' => ['recipient@example.com'],
        'subject' => 'Test Subject',
        'text' => 'Plain text body',
    ]);

    $response = $service->send($data);

    expect($transporter->lastUri)->toBe('emails')
        ->and($transporter->lastData['from'])->toBe('sender@example.com')
        ->and($transporter->lastData['to'])->toBe(['recipient@example.com'])
        ->and($transporter->lastData['subject'])->toBe('Test Subject')
        ->and($transporter->lastData['text'])->toBe('Plain text body')
        ->and($response)->toBeInstanceOf(SendEmailResponse::class)
        ->and((string) $response->requestId)->toBe('req_123')
        ->and($response->accepted)->toBe(1)
        ->and($response->rejected)->toBe(0);
});

test('send method works with EmailBuilder', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_456', 'accepted' => 1, 'rejected' => 0];

    $service = new EmailService($transporter);
    $builder = $service->create()
        ->from('sender@example.com')
        ->to(['recipient@example.com'])
        ->subject('Test Subject')
        ->html('<p>HTML body</p>');

    $response = $service->send($builder);

    expect($transporter->lastUri)->toBe('emails')
        ->and($response)->toBeInstanceOf(SendEmailResponse::class)
        ->and((string) $response->requestId)->toBe('req_456');
});

test('sendHtml helper sends HTML email', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_html', 'accepted' => 1, 'rejected' => 0];

    $service = new EmailService($transporter);
    $response = $service->sendHtml(
        from: 'sender@example.com',
        to: 'recipient@example.com',
        subject: 'HTML Test',
        html: '<h1>Hello</h1>',
    );

    expect($transporter->lastData['from'])->toBe('sender@example.com')
        ->and($transporter->lastData['to'])->toBe(['recipient@example.com'])
        ->and($transporter->lastData['subject'])->toBe('HTML Test')
        ->and($transporter->lastData['html'])->toBe('<h1>Hello</h1>')
        ->and($transporter->lastData)->not->toHaveKey('text')
        ->and((string) $response->requestId)->toBe('req_html');
});

test('sendHtml helper with from name using EmailAddress', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_html2', 'accepted' => 1, 'rejected' => 0];

    $service = new EmailService($transporter);
    $service->sendHtml(
        from: new EmailAddress('sender@example.com', 'Sender Name'),
        to: ['recipient@example.com'],
        subject: 'HTML Test',
        html: '<h1>Hello</h1>',
    );

    expect($transporter->lastData['from'])->toBe('sender@example.com')
        ->and($transporter->lastData['from_name'])->toBe('Sender Name');
});

test('sendText helper sends plain text email', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_text', 'accepted' => 1, 'rejected' => 0];

    $service = new EmailService($transporter);
    $response = $service->sendText(
        from: 'sender@example.com',
        to: 'recipient@example.com',
        subject: 'Text Test',
        text: 'Hello World',
    );

    expect($transporter->lastData['from'])->toBe('sender@example.com')
        ->and($transporter->lastData['to'])->toBe(['recipient@example.com'])
        ->and($transporter->lastData['subject'])->toBe('Text Test')
        ->and($transporter->lastData['text'])->toBe('Hello World')
        ->and($transporter->lastData)->not->toHaveKey('html')
        ->and((string) $response->requestId)->toBe('req_text');
});

test('sendTemplate helper sends template email', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_tpl', 'accepted' => 1, 'rejected' => 0];

    $service = new EmailService($transporter);
    $response = $service->sendTemplate(
        from: 'sender@example.com',
        to: 'recipient@example.com',
        subject: 'Template Test',
        templateSlug: 'welcome-email',
        templateVersion: 2,
        projectId: 123,
        substitutionData: ['name' => 'John'],
    );

    expect($transporter->lastData['from'])->toBe('sender@example.com')
        ->and($transporter->lastData['to'])->toBe(['recipient@example.com'])
        ->and($transporter->lastData['subject'])->toBe('Template Test')
        ->and($transporter->lastData['template_slug'])->toBe('welcome-email')
        ->and($transporter->lastData['template_version'])->toBe(2)
        ->and($transporter->lastData['project_id'])->toBe(123)
        ->and($transporter->lastData['substitution_data'])->toBe(['name' => 'John'])
        ->and((string) $response->requestId)->toBe('req_tpl');
});

test('sendTemplate helper without optional parameters', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_tpl2', 'accepted' => 1, 'rejected' => 0];

    $service = new EmailService($transporter);
    $service->sendTemplate(
        from: 'sender@example.com',
        to: ['recipient@example.com'],
        subject: 'Template Test',
        templateSlug: 'simple-template',
    );

    expect($transporter->lastData['template_slug'])->toBe('simple-template')
        ->and($transporter->lastData)->not->toHaveKey('template_version')
        ->and($transporter->lastData)->not->toHaveKey('project_id')
        ->and($transporter->lastData)->not->toHaveKey('substitution_data');
});

test('sendTemplate helper without subject lets template define it', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_tpl3', 'accepted' => 1, 'rejected' => 0];

    $service = new EmailService($transporter);
    $service->sendTemplate(
        from: 'sender@example.com',
        to: 'recipient@example.com',
        templateSlug: 'welcome-email',
    );

    expect($transporter->lastData['template_slug'])->toBe('welcome-email')
        ->and($transporter->lastData)->not->toHaveKey('subject');
});

test('sendTemplate accepts positional arguments in new order', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_tpl4', 'accepted' => 1, 'rejected' => 0];

    $service = new EmailService($transporter);
    $service->sendTemplate(
        'sender@example.com',
        'recipient@example.com',
        'welcome-email',
    );

    expect($transporter->lastData['template_slug'])->toBe('welcome-email')
        ->and($transporter->lastData)->not->toHaveKey('subject');
});

test('send includes quota from response headers', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_quota', 'accepted' => 1, 'rejected' => 0];
    $transporter->responseHeaders = [
        'X-Monthly-Limit' => '3000',
        'X-Monthly-Remaining' => '2500',
        'X-Monthly-Reset' => '1740787200',
        'X-Daily-Limit' => '100',
        'X-Daily-Remaining' => '75',
        'X-Daily-Reset' => '1739600000',
    ];

    $service = new EmailService($transporter);
    $data = SendEmailData::from([
        'from' => 'sender@example.com',
        'to' => ['recipient@example.com'],
        'subject' => 'Test',
        'text' => 'Body',
    ]);

    $response = $service->send($data);

    expect($response->quota)->toBeInstanceOf(SendingQuota::class)
        ->and($response->quota->monthlyLimit)->toBe(3000)
        ->and($response->quota->monthlyRemaining)->toBe(2500)
        ->and($response->quota->monthlyReset)->toBe(1740787200)
        ->and($response->quota->dailyLimit)->toBe(100)
        ->and($response->quota->dailyRemaining)->toBe(75)
        ->and($response->quota->dailyReset)->toBe(1739600000);
});

test('send returns null quota when no headers present', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_noquota', 'accepted' => 1, 'rejected' => 0];
    $transporter->responseHeaders = [];

    $service = new EmailService($transporter);
    $data = SendEmailData::from([
        'from' => 'sender@example.com',
        'to' => ['recipient@example.com'],
        'subject' => 'Test',
        'text' => 'Body',
    ]);

    $response = $service->send($data);

    expect($response->quota)->toBeNull();
});

test('list returns ListEmailsResponse with cursor pagination', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'events' => [
            'data' => [
                [
                    'event_id' => 'evt_1',
                    'type' => 'injection',
                    'timestamp' => '2026-04-18T10:00:00+00:00',
                    'request_id' => 'req_123',
                    'subject' => 'Hello',
                    'rcpt_to' => 'to@example.com',
                ],
            ],
            'total_count' => 1,
            'from' => '2026-04-08T00:00:00+00:00',
            'to' => '2026-04-18T23:59:59+00:00',
            'pagination' => [
                'next_cursor' => 'cur_next',
                'per_page' => 25,
            ],
        ],
    ];

    $service = new EmailService($transporter);
    $response = $service->list();

    expect($transporter->lastUri)->toBe('emails')
        ->and($transporter->lastQuery)->toBe([])
        ->and($response)->toBeInstanceOf(ListEmailsResponse::class)
        ->and($response->emails)->toHaveCount(1)
        ->and($response->emails[0]->eventId)->toBe('evt_1')
        ->and($response->totalCount)->toBe(1)
        ->and($response->pagination->nextCursor)->toBe('cur_next')
        ->and($response->pagination->perPage)->toBe(25)
        ->and($response->hasMore())->toBeTrue();
});

test('list forwards filter query params', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'events' => [
            'data' => [],
            'total_count' => 0,
            'from' => '2026-04-01T00:00:00+00:00',
            'to' => '2026-04-18T00:00:00+00:00',
            'pagination' => ['next_cursor' => null, 'per_page' => 10],
        ],
    ];

    $service = new EmailService($transporter);
    $filter = ListEmailsFilter::create()
        ->perPage(10)
        ->recipients('foo@example.com')
        ->from('2026-04-01T00:00:00Z')
        ->to('2026-04-18T00:00:00Z');

    $response = $service->list($filter);

    expect($transporter->lastUri)->toBe('emails')
        ->and($transporter->lastQuery)->toBe([
            'per_page' => 10,
            'recipients' => 'foo@example.com',
            'from' => '2026-04-01T00:00:00Z',
            'to' => '2026-04-18T00:00:00Z',
        ])
        ->and($response->hasMore())->toBeFalse();
});

test('events returns ListEmailEventsResponse', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'events' => [
            'data' => [
                [
                    'event_id' => 'evt_click',
                    'type' => 'click',
                    'timestamp' => '2026-04-18T10:05:00+00:00',
                    'request_id' => 'req_123',
                    'rcpt_to' => 'to@example.com',
                    'target_link_url' => 'https://example.com',
                ],
            ],
            'total_count' => 1,
            'from' => '2026-04-08T00:00:00+00:00',
            'to' => '2026-04-18T23:59:59+00:00',
            'pagination' => ['next_cursor' => null, 'per_page' => 25],
        ],
    ];

    $service = new EmailService($transporter);
    $filter = ListEmailEventsFilter::create()
        ->events(['click', 'open'])
        ->recipients(['to@example.com']);

    $response = $service->events($filter);

    expect($transporter->lastUri)->toBe('emails/events')
        ->and($transporter->lastQuery['events'])->toBe('click,open')
        ->and($transporter->lastQuery['recipients'])->toBe('to@example.com')
        ->and($response)->toBeInstanceOf(ListEmailEventsResponse::class)
        ->and($response->events)->toHaveCount(1)
        ->and($response->events[0]->type)->toBe('click')
        ->and($response->events[0]->targetLinkUrl)->toBe('https://example.com');
});

test('find hits GET /emails/{requestId} and returns TransmissionDetail', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'transmission_id' => 'req_abc',
        'state' => 'delivered',
        'scheduled_at' => null,
        'from' => 'sender@example.com',
        'from_name' => null,
        'subject' => 'Welcome',
        'recipients' => ['r@example.com'],
        'num_recipients' => 1,
        'events' => [],
    ];

    $service = new EmailService($transporter);
    $detail = $service->find('req_abc');

    expect($transporter->lastUri)->toBe('emails/req_abc')
        ->and($detail)->toBeInstanceOf(TransmissionDetail::class)
        ->and($detail->transmissionId)->toBe('req_abc')
        ->and($detail->state)->toBe(TransmissionState::Delivered)
        ->and($detail->numRecipients)->toBe(1);
});

test('find forwards from/to query params when provided', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'transmission_id' => 'req_abc',
        'state' => 'scheduled',
        'from' => 'sender@example.com',
        'subject' => 'Hello',
        'recipients' => [],
        'num_recipients' => 0,
        'events' => [],
    ];

    $service = new EmailService($transporter);
    $service->find('req_abc', from: '2026-04-01', to: '2026-04-18');

    expect($transporter->lastUri)->toBe('emails/req_abc')
        ->and($transporter->lastQuery)->toBe([
            'from' => '2026-04-01',
            'to' => '2026-04-18',
        ]);
});

test('schedule posts to /emails/scheduled', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = ['request_id' => 'req_sched', 'accepted' => 1, 'rejected' => 0];

    $service = new EmailService($transporter);
    $data = SendEmailData::from([
        'from' => 'sender@example.com',
        'to' => ['recipient@example.com'],
        'subject' => 'Later',
        'text' => 'Body',
        'scheduled_at' => '2026-04-19T12:00:00Z',
    ]);

    $response = $service->schedule($data);

    expect($transporter->lastUri)->toBe('emails/scheduled')
        ->and($transporter->lastData['scheduled_at'])->toBe('2026-04-19T12:00:00Z')
        ->and((string) $response->requestId)->toBe('req_sched');
});

test('getScheduled returns TransmissionDetail', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'transmission_id' => 'tx_123',
        'state' => 'scheduled',
        'scheduled_at' => '2026-04-19T12:00:00+00:00',
        'from' => 'sender@example.com',
        'from_name' => 'Sender Name',
        'subject' => 'Later',
        'recipients' => ['r@example.com'],
        'num_recipients' => 1,
        'events' => [],
    ];

    $service = new EmailService($transporter);
    $scheduled = $service->getScheduled('tx_123');

    expect($transporter->lastUri)->toBe('emails/scheduled/tx_123')
        ->and($scheduled)->toBeInstanceOf(TransmissionDetail::class)
        ->and($scheduled->transmissionId)->toBe('tx_123')
        ->and($scheduled->state)->toBe(TransmissionState::Scheduled)
        ->and($scheduled->fromName)->toBe('Sender Name')
        ->and($scheduled->numRecipients)->toBe(1);
});

test('cancelScheduled deletes /emails/scheduled/{id}', function (): void {
    $transporter = new MockTransporter;
    $service = new EmailService($transporter);

    $service->cancelScheduled('tx_987');

    expect($transporter->lastUri)->toBe('emails/scheduled/tx_987');
});
