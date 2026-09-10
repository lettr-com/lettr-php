<?php

declare(strict_types=1);

use Lettr\Builders\EmailBuilder;
use Lettr\Client;
use Lettr\Contracts\SupportsRequestHeaders;
use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Email\SendEmailData;
use Lettr\Exceptions\ConflictException;
use Lettr\Exceptions\IdempotencyConflictException;
use Lettr\Exceptions\IdempotencyInProgressException;
use Lettr\Exceptions\InvalidValueException;
use Lettr\Services\EmailService;
use Lettr\ValueObjects\IdempotencyKey;
use Tests\Support\MockTransporter;

function sendResponse(): array
{
    return ['request_id' => 'req-1', 'accepted' => 1, 'rejected' => 0];
}

function idempotentBuilder(EmailService $service): EmailBuilder
{
    return $service->create()
        ->from('sender@example.com')
        ->to(['recipient@example.com'])
        ->subject('Hello')
        ->html('<p>Hi</p>');
}

// ---------------------------------------------------------------------------
// The key itself
// ---------------------------------------------------------------------------

test('an idempotency key accepts the documented format', function (): void {
    expect((new IdempotencyKey('order-confirmation-12345'))->value)->toBe('order-confirmation-12345')
        ->and((string) new IdempotencyKey('a.b_c-1'))->toBe('a.b_c-1')
        ->and((new IdempotencyKey('k'))->toArray())->toBe(['Idempotency-Key' => 'k']);
});

/**
 * Validated client-side so a malformed key fails on your machine instead of
 * costing a round trip and a 422.
 */
test('an invalid idempotency key throws before any request goes out', function (string $bad): void {
    expect(fn (): IdempotencyKey => new IdempotencyKey($bad))->toThrow(InvalidValueException::class);
})->with([
    'empty' => '',
    'space' => 'order 123',
    'slash' => 'order/123',
    'colon' => 'order:123',
    'unicode' => 'order-č',
    'too long' => [str_repeat('a', 256)],
]);

test('a payload-derived key is deterministic and valid', function (): void {
    $payload = ['from' => 'a@example.com', 'to' => ['b@example.com'], 'subject' => 'Hi'];

    $first = IdempotencyKey::forPayload($payload);
    $second = IdempotencyKey::forPayload($payload);
    $other = IdempotencyKey::forPayload($payload + ['html' => '<p>x</p>']);

    expect($first->value)->toBe($second->value)
        ->and($first->value)->not->toBe($other->value)
        ->and(strlen($first->value))->toBe(64);
});

// ---------------------------------------------------------------------------
// Sending it
// ---------------------------------------------------------------------------

test('send puts the key in the header, never in the body', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sendResponse();

    $service = new EmailService($transporter);
    $service->send(idempotentBuilder($service)->idempotencyKey('order-12345'));

    expect($transporter->lastHeaders)->toBe(['Idempotency-Key' => 'order-12345'])
        ->and($transporter->lastData)->not->toHaveKey('idempotency_key')
        ->and($transporter->lastData)->not->toHaveKey('idempotencyKey');
});

test('the key can be passed to send instead of the builder', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sendResponse();

    $service = new EmailService($transporter);
    $service->send(idempotentBuilder($service), 'order-12345');

    expect($transporter->lastHeaders)->toBe(['Idempotency-Key' => 'order-12345']);
});

test('the send argument wins over a key already on the builder', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sendResponse();

    $service = new EmailService($transporter);
    $service->send(idempotentBuilder($service)->idempotencyKey('from-builder'), 'from-argument');

    expect($transporter->lastHeaders)->toBe(['Idempotency-Key' => 'from-argument']);
});

/**
 * The compatibility guarantee: a caller that says nothing about idempotency
 * sends the exact request it sent before this existed.
 */
test('no key means no header at all', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sendResponse();

    $service = new EmailService($transporter);
    $service->send(idempotentBuilder($service));

    expect($transporter->lastHeaders)->toBe([]);
});

test('SendEmailData keeps the key out of the request body', function (): void {
    $data = SendEmailData::from([
        'from' => 'sender@example.com',
        'to' => ['recipient@example.com'],
        'subject' => 'Hello',
        'html' => '<p>Hi</p>',
        'idempotency_key' => 'order-12345',
    ]);

    expect($data->idempotencyKey?->value)->toBe('order-12345')
        ->and($data->toArray())->not->toHaveKey('idempotency_key');
});

// ---------------------------------------------------------------------------
// Reading the answer
// ---------------------------------------------------------------------------

test('a replayed send is a success that says it was replayed', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sendResponse();
    $transporter->responseHeaders = ['Idempotency-Replayed' => 'true'];

    $service = new EmailService($transporter);
    $response = $service->send(idempotentBuilder($service)->idempotencyKey('order-12345'));

    // No second email went out, but nothing failed either.
    expect($response->replayed)->toBeTrue()
        ->and($response->accepted)->toBe(1);
});

test('a normal send is not marked replayed', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sendResponse();

    $service = new EmailService($transporter);

    expect($service->send(idempotentBuilder($service))->replayed)->toBeFalse();
});

test('the replay header is read case-insensitively', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sendResponse();
    $transporter->responseHeaders = ['idempotency-replayed' => 'TRUE'];

    $service = new EmailService($transporter);

    expect($service->send(idempotentBuilder($service))->replayed)->toBeTrue();
});

// ---------------------------------------------------------------------------
// The two conflicts
// ---------------------------------------------------------------------------

/**
 * These are the whole reason the SDK has to tell 409s apart: one is safe to
 * retry with the same key and the other will fail forever.
 */
test('the in-progress conflict is retryable and carries Retry-After', function (): void {
    $e = new IdempotencyInProgressException('still processing', retryAfter: 3);

    expect($e->retryAfter)->toBe(3)
        ->and($e)->toBeInstanceOf(ConflictException::class)
        ->and($e->getCode())->toBe(409);
});

test('the payload conflict is a caller bug and must not be retried', function (): void {
    $e = new IdempotencyConflictException;

    expect($e)->toBeInstanceOf(ConflictException::class)
        ->and($e->getCode())->toBe(409);
});

test('both stay catchable as ConflictException so existing handlers keep working', function (): void {
    foreach ([new IdempotencyConflictException, new IdempotencyInProgressException] as $e) {
        $caught = null;

        try {
            throw $e;
        } catch (ConflictException $conflict) {
            $caught = $conflict;
        }

        expect($caught)->toBe($e);
    }
});

// ---------------------------------------------------------------------------
// Custom transporters
// ---------------------------------------------------------------------------

/**
 * The cost of not breaking TransporterContract: a transporter that predates
 * SupportsRequestHeaders keeps working, and silently sends no key.
 */
test('a transporter that cannot carry headers still sends, without the key', function (): void {
    $transporter = new class implements TransporterContract
    {
        /** @var array<string, mixed>|null */
        public ?array $lastData = null;

        public function post(string $uri, array $data): array
        {
            $this->lastData = $data;

            return sendResponse();
        }

        public function postExpectingEnvelope(string $uri, ?array $data = null): array
        {
            return [];
        }

        public function get(string $uri): array
        {
            return [];
        }

        public function getWithQuery(string $uri, array $query = []): array
        {
            return [];
        }

        public function put(string $uri, array $data): array
        {
            return [];
        }

        public function patch(string $uri, array $data): array
        {
            return [];
        }

        public function delete(string $uri): void {}

        public function deleteWithBody(string $uri, array $data): array
        {
            return [];
        }

        public function lastResponseHeaders(): array
        {
            return [];
        }

        public function lastStatusCode(): ?int
        {
            return null;
        }
    };

    $service = new EmailService($transporter);
    $response = $service->send(idempotentBuilder($service)->idempotencyKey('order-12345'));

    expect($transporter)->not->toBeInstanceOf(SupportsRequestHeaders::class)
        ->and($response->accepted)->toBe(1)
        ->and($transporter->lastData)->not->toBeNull();
});

test('the shipped client can carry headers', function (): void {
    expect(new Client('test-api-key'))->toBeInstanceOf(SupportsRequestHeaders::class);
});
