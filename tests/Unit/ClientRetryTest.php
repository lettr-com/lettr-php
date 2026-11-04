<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Lettr\Client;
use Lettr\Exceptions\ApiException;
use Lettr\Exceptions\InvalidValueException;
use Lettr\Exceptions\QuotaExceededException;
use Lettr\Exceptions\RateLimitException;
use Lettr\Lettr;
use Lettr\RetryConfig;

/**
 * A Client wired to canned responses, a fake clock and a recording sleeper,
 * so retry and pacing can be tested without network calls or real sleeps.
 *
 * Sleeping advances the fake clock, like real time would.
 */
final class RetryHarness
{
    /** @var array<int, float> */
    public array $sleeps = [];

    /** @var array<int, array<string, mixed>> */
    public array $history = [];

    public float $now = 1_793_773_588.0;

    public Client $client;

    /**
     * @param  array<int, Response>  $responses
     */
    public function __construct(array $responses, ?RetryConfig $retry = null)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $client = (new ReflectionClass(Client::class))->newInstanceWithoutConstructor();

        foreach ([
            'apiKey' => 'test-api-key',
            'userAgent' => 'lettr-php/test',
            'httpClient' => new GuzzleClient(['handler' => $stack, 'base_uri' => 'https://app.lettr.com/api/']),
            'retry' => $retry,
            'sleeper' => function (float $seconds): void {
                $this->sleeps[] = $seconds;
                $this->now += $seconds;
            },
            'clock' => fn (): float => $this->now,
        ] as $name => $value) {
            (new ReflectionProperty(Client::class, $name))->setValue($client, $value);
        }

        $this->client = $client;
    }
}

function rateLimited(?string $retryAfter = '1', string $errorCode = 'rate_limit_exceeded'): Response
{
    $headers = ['X-RateLimit-Limit' => '3', 'X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => '1793773589'];

    if ($retryAfter !== null) {
        $headers['Retry-After'] = $retryAfter;
    }

    return new Response(429, $headers, (string) json_encode([
        'message' => 'Too many requests.',
        'error_code' => $errorCode,
    ]));
}

/**
 * @param  array<string, string>  $headers
 */
function ok(array $headers = []): Response
{
    return new Response(200, $headers, (string) json_encode(['data' => ['ok' => true]]));
}

describe('RetryConfig', function (): void {
    test('defaults to 3 retries, 5 second cap, pacing on', function (): void {
        $config = RetryConfig::default();

        expect($config->maxRetries)->toBe(3)
            ->and($config->maxWaitSeconds)->toBe(5)
            ->and($config->pace)->toBeTrue();
    });

    test('disabled turns off retries and pacing', function (): void {
        $config = RetryConfig::disabled();

        expect($config->maxRetries)->toBe(0)
            ->and($config->pace)->toBeFalse();
    });

    test('rejects negative values', function (): void {
        expect(fn () => new RetryConfig(maxRetries: -1))->toThrow(InvalidValueException::class)
            ->and(fn () => new RetryConfig(maxWaitSeconds: -1))->toThrow(InvalidValueException::class);
    });

    test('Client and Lettr::client still work without it', function (): void {
        expect(new Client('key'))->toBeInstanceOf(Client::class)
            ->and(new Client('key', 'suffix/1.0', RetryConfig::disabled()))->toBeInstanceOf(Client::class)
            ->and(Lettr::client('key', null, RetryConfig::disabled()))->toBeInstanceOf(Lettr::class);
    });
});

describe('retry', function (): void {
    test('a rate-limit 429 is retried after Retry-After and then succeeds', function (): void {
        $h = new RetryHarness([rateLimited('1'), ok()]);

        expect($h->client->post('emails', []))->toBe(['ok' => true])
            ->and($h->history)->toHaveCount(2)
            ->and($h->sleeps)->toBe([1.0]);
    });

    test('the retried request is the same request', function (): void {
        $h = new RetryHarness([rateLimited('0'), ok()]);

        $h->client->post('emails', ['to' => ['a@example.com']]);

        $first = $h->history[0]['request'];
        $second = $h->history[1]['request'];

        expect((string) $second->getUri())->toBe((string) $first->getUri())
            ->and((string) $second->getBody())->toBe((string) $first->getBody());
    });

    test('RateLimitException is thrown only after retries are exhausted', function (): void {
        $h = new RetryHarness([rateLimited(), rateLimited(), rateLimited(), rateLimited()]);

        try {
            $h->client->get('domains');
            $this->fail('Expected RateLimitException.');
        } catch (RateLimitException $e) {
            // The body was read to check for quota; it must still reach the exception.
            expect($e->getMessage())->toBe('Too many requests.')
                ->and($e->retryAfter)->toBe(1)
                ->and($e->rateLimit?->remaining)->toBe(0);
        }

        expect($h->history)->toHaveCount(4)
            ->and($h->sleeps)->toBe([1.0, 1.0, 1.0]);
    });

    test('maxRetries is honoured', function (): void {
        $h = new RetryHarness([rateLimited(), rateLimited(), ok()], new RetryConfig(maxRetries: 1));

        expect(fn () => $h->client->get('domains'))->toThrow(RateLimitException::class)
            ->and($h->history)->toHaveCount(2);
    });

    test('disabled throws on the first 429 without sleeping', function (): void {
        $h = new RetryHarness([rateLimited(), ok()], RetryConfig::disabled());

        expect(fn () => $h->client->get('domains'))->toThrow(RateLimitException::class)
            ->and($h->history)->toHaveCount(1)
            ->and($h->sleeps)->toBe([]);
    });

    test('a quota 429 is never retried', function (string $code): void {
        $h = new RetryHarness([rateLimited('1', $code), ok()]);

        expect(fn () => $h->client->post('emails', []))->toThrow(QuotaExceededException::class)
            ->and($h->history)->toHaveCount(1)
            ->and($h->sleeps)->toBe([]);
    })->with(['quota_exceeded', 'daily_quota_exceeded']);

    test('a Retry-After longer than maxWaitSeconds throws immediately', function (): void {
        $h = new RetryHarness([rateLimited('30'), ok()]);

        expect(fn () => $h->client->get('domains'))->toThrow(RateLimitException::class)
            ->and($h->history)->toHaveCount(1)
            ->and($h->sleeps)->toBe([]);
    });

    test('without Retry-After it backs off exponentially, capped at maxWaitSeconds', function (): void {
        $h = new RetryHarness(
            [rateLimited(null), rateLimited(null), rateLimited(null), ok()],
            new RetryConfig(maxRetries: 3, maxWaitSeconds: 1, pace: false),
        );

        $h->client->get('domains');

        expect($h->sleeps)->toBe([0.5, 1.0, 1.0]);
    });

    test('an HTTP-date Retry-After is understood', function (): void {
        $h = new RetryHarness([rateLimited(gmdate('D, d M Y H:i:s', 1_793_773_590).' GMT'), ok()]);

        $h->client->get('domains');

        expect($h->sleeps)->toBe([2.0]);
    });

    test('server errors are not retried', function (int $status): void {
        $h = new RetryHarness([new Response($status, [], '{"message":"boom"}'), ok()]);

        expect(fn () => $h->client->post('emails', []))->toThrow(ApiException::class)
            ->and($h->history)->toHaveCount(1);
    })->with([500, 502, 503]);
});

describe('pacing', function (): void {
    test('waits for the window to reset after a response with nothing remaining', function (): void {
        $h = new RetryHarness([
            ok(['X-RateLimit-Limit' => '3', 'X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => '1793773589']),
            ok(),
        ]);
        $h->now = 1_793_773_588.75;

        $h->client->get('domains');
        expect($h->sleeps)->toBe([]);

        $h->client->get('domains');
        expect($h->sleeps)->toBe([0.25]);
    });

    test('does not wait while requests remain', function (): void {
        $h = new RetryHarness([
            ok(['X-RateLimit-Limit' => '3', 'X-RateLimit-Remaining' => '1', 'X-RateLimit-Reset' => '1793773589']),
            ok(),
        ]);

        $h->client->get('domains');
        $h->client->get('domains');

        expect($h->sleeps)->toBe([]);
    });

    test('does not wait when the window already reset by the next call', function (): void {
        $h = new RetryHarness([
            ok(['X-RateLimit-Limit' => '3', 'X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => '1793773589']),
            ok(),
        ]);

        $h->client->get('domains');
        $h->now += 2;
        $h->client->get('domains');

        expect($h->sleeps)->toBe([]);
    });

    test('a local clock running behind is bounded by the server Date header', function (): void {
        $h = new RetryHarness([
            ok([
                'X-RateLimit-Limit' => '3',
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => '1793773589',
                'Date' => gmdate('D, d M Y H:i:s', 1_793_773_588).' GMT',
            ]),
            ok(),
        ]);
        // Local clock 60 seconds behind the server.
        $h->now = 1_793_773_528.0;

        $h->client->get('domains');
        $h->client->get('domains');

        expect($h->sleeps)->toBe([1.0]);
    });

    test('never waits longer than maxWaitSeconds', function (): void {
        $h = new RetryHarness([
            ok(['X-RateLimit-Limit' => '3', 'X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => '1793773700']),
            ok(),
        ]);

        $h->client->get('domains');
        $h->client->get('domains');

        expect($h->sleeps)->toBe([5.0]);
    });

    test('pace: false never waits', function (): void {
        $h = new RetryHarness([
            ok(['X-RateLimit-Limit' => '3', 'X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => '1793773589']),
            ok(),
        ], new RetryConfig(pace: false));

        $h->client->get('domains');
        $h->client->get('domains');

        expect($h->sleeps)->toBe([]);
    });
});
