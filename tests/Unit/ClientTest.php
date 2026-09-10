<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Lettr\Client;
use Lettr\Contracts\SupportsRequestHeaders;
use Lettr\Contracts\TransporterContract;
use Lettr\Exceptions\ConflictException;
use Lettr\Exceptions\IdempotencyConflictException;
use Lettr\Exceptions\IdempotencyInProgressException;
use Psr\Http\Message\RequestInterface;

function clientUserAgent(Client $client): string
{
    $property = new ReflectionProperty(Client::class, 'userAgent');

    return $property->getValue($client);
}

test('can create Client instance', function (): void {
    $client = new Client('test-api-key');

    expect($client)->toBeInstanceOf(Client::class);
});

test('implements TransporterContract', function (): void {
    $client = new Client('test-api-key');

    expect($client)->toBeInstanceOf(TransporterContract::class);
});

test('builds a lettr-php User-Agent with the resolved version', function (): void {
    $client = new Client('test-api-key');

    expect(clientUserAgent($client))->toStartWith('lettr-php/');
});

test('appends a wrapping package suffix to the User-Agent', function (): void {
    $client = new Client('test-api-key', 'lettr-laravel/2.2.0');

    expect(clientUserAgent($client))
        ->toStartWith('lettr-php/')
        ->toEndWith(' lettr-laravel/2.2.0');
});

test('omits the suffix segment when none is given', function (): void {
    $client = new Client('test-api-key');

    expect(clientUserAgent($client))->not->toContain(' ');
});

test('strips CR/LF from the suffix to prevent header injection', function (): void {
    $client = new Client('test-api-key', "evil\r\nX-Injected: 1");

    expect(clientUserAgent($client))
        ->not->toContain("\r")
        ->and(clientUserAgent($client))->not->toContain("\n")
        ->and(clientUserAgent($client))->toEndWith(' evilX-Injected: 1');
});

/**
 * Swap the Guzzle client for a mock handler so the 409 branching in
 * `handleGuzzleException()` can be exercised without a network call.
 *
 * @param  array<int, Response>  $responses
 * @param  array<int, array{request: RequestInterface}>  $history
 */
function clientWithResponses(array $responses, array &$history = []): Client
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);

    $stack->push(Middleware::history($history));

    // `$httpClient` is readonly, so it can only be written while still
    // uninitialised - hence bypassing the constructor rather than overwriting
    // what it built.
    $client = (new ReflectionClass(Client::class))->newInstanceWithoutConstructor();

    foreach ([
        'apiKey' => 'test-api-key',
        'userAgent' => 'lettr-php/test',
        'httpClient' => new GuzzleClient([
            'handler' => $stack,
            'base_uri' => 'https://app.lettr.com/api/',
        ]),
    ] as $name => $value) {
        (new ReflectionProperty(Client::class, $name))->setValue($client, $value);
    }

    return $client;
}

test('the client implements SupportsRequestHeaders', function (): void {
    expect(new Client('test-api-key'))->toBeInstanceOf(SupportsRequestHeaders::class);
});

test('postWithHeaders merges the header over the defaults', function (): void {
    $sent = [];
    $client = clientWithResponses([
        new Response(200, [], (string) json_encode(['data' => ['ok' => true]])),
    ], $sent);

    $client->postWithHeaders('emails', ['from' => 'a@example.com'], ['Idempotency-Key' => 'order-12345']);

    $request = $sent[0]['request'];

    expect($request->getHeaderLine('Idempotency-Key'))->toBe('order-12345')
        // The defaults are still there - the extra header adds, it does not replace.
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer test-api-key')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($request->getHeaderLine('User-Agent'))->toBe('lettr-php/test');
});

/**
 * The two 409s must not be conflated: one is safe to retry with the same key,
 * the other will fail forever.
 */
test('an in-progress 409 becomes a retryable exception carrying Retry-After', function (): void {
    $client = clientWithResponses([
        new Response(409, ['Retry-After' => '1'], (string) json_encode([
            'message' => 'A request with this Idempotency-Key is still processing. Retry with the same key.',
            'error_code' => 'idempotency_in_progress',
        ])),
    ]);

    try {
        $client->post('emails', []);
        $this->fail('Expected IdempotencyInProgressException.');
    } catch (IdempotencyInProgressException $e) {
        expect($e->retryAfter)->toBe(1)
            ->and($e->errorCode)->toBe('idempotency_in_progress')
            ->and($e->getMessage())->toContain('still processing');
    }
});

test('a payload-conflict 409 becomes the non-retryable exception', function (): void {
    $client = clientWithResponses([
        new Response(409, [], (string) json_encode([
            'message' => 'This Idempotency-Key was already used with a different request payload.',
            'error_code' => 'idempotency_key_conflict',
        ])),
    ]);

    expect(fn () => $client->post('emails', []))
        ->toThrow(IdempotencyConflictException::class);
});

test('any other 409 stays a plain ConflictException', function (): void {
    $client = clientWithResponses([
        new Response(409, [], (string) json_encode([
            'message' => 'A contact with this email already exists.',
            'error_code' => 'resource_already_exists',
        ])),
    ]);

    try {
        $client->post('audience/contacts', []);
        $this->fail('Expected ConflictException.');
    } catch (ConflictException $e) {
        expect($e)->not->toBeInstanceOf(IdempotencyConflictException::class)
            ->and($e)->not->toBeInstanceOf(IdempotencyInProgressException::class);
    }
});
