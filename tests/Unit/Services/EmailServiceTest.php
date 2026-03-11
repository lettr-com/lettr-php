<?php

declare(strict_types=1);

use Lettr\Builders\EmailBuilder;
use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Email\SendEmailData;
use Lettr\Dto\Email\SendEmailResponse;
use Lettr\Dto\SendingQuota;
use Lettr\Services\EmailService;
use Lettr\ValueObjects\EmailAddress;

/**
 * Simple mock transporter for testing.
 */
class MockTransporter implements TransporterContract
{
    public ?string $lastUri = null;

    /** @var array<string, mixed>|null */
    public ?array $lastData = null;

    /** @var array<string, mixed>|null */
    public ?array $lastQuery = null;

    /** @var array<string, mixed> */
    public array $response = [];

    /** @var array<string, string|string[]> */
    public array $responseHeaders = [];

    public function post(string $uri, array $data): array
    {
        $this->lastUri = $uri;
        $this->lastData = $data;

        return $this->response;
    }

    public function get(string $uri): array
    {
        $this->lastUri = $uri;

        return $this->response;
    }

    public function getWithQuery(string $uri, array $query = []): array
    {
        $this->lastUri = $uri;
        $this->lastQuery = $query;

        return $this->response;
    }

    public function delete(string $uri): void
    {
        $this->lastUri = $uri;
    }

    public function lastResponseHeaders(): array
    {
        return $this->responseHeaders;
    }
}

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
        subject: null,
        templateSlug: 'welcome-email',
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
