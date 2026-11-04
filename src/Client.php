<?php

declare(strict_types=1);

namespace Lettr;

use Closure;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Lettr\Contracts\SupportsDeleteWithResponse;
use Lettr\Contracts\SupportsRequestHeaders;
use Lettr\Contracts\TransporterContract;
use Lettr\Dto\RateLimit;
use Lettr\Dto\SendingQuota;
use Lettr\Exceptions\ApiException;
use Lettr\Exceptions\ConflictException;
use Lettr\Exceptions\ForbiddenException;
use Lettr\Exceptions\IdempotencyConflictException;
use Lettr\Exceptions\IdempotencyInProgressException;
use Lettr\Exceptions\NotFoundException;
use Lettr\Exceptions\QuotaExceededException;
use Lettr\Exceptions\RateLimitException;
use Lettr\Exceptions\TransporterException;
use Lettr\Exceptions\UnauthorizedException;
use Lettr\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Client for Lettr API.
 */
final class Client implements SupportsDeleteWithResponse, SupportsRequestHeaders, TransporterContract
{
    private readonly ClientInterface $httpClient;

    private readonly string $apiKey;

    private readonly string $userAgent;

    /** @var array<string, string|string[]> */
    private array $lastHeaders = [];

    private ?int $lastStatusCode = null;

    /**
     * Rate-limit 429 error codes that mean "out of quota", not "too fast".
     * Waiting a few seconds will not help, so these are never retried.
     */
    private const QUOTA_ERROR_CODES = ['quota_exceeded', 'daily_quota_exceeded'];

    /** Null means {@see RetryConfig::default()}. */
    private ?RetryConfig $retry = null;

    /**
     * Local time (from {@see self::now()}) before which the next request must
     * not be sent, because the last response said the window was used up.
     */
    private ?float $pauseUntil = null;

    /**
     * Test seams, set by reflection so they stay out of the public API.
     * Null sleeps for real and reads the system clock.
     *
     * @var (Closure(float): void)|null
     */
    private ?Closure $sleeper = null; // @phpstan-ignore property.unusedType

    /** @var (Closure(): float)|null */
    private ?Closure $clock = null; // @phpstan-ignore property.unusedType

    /**
     * @param  string|null  $userAgentSuffix  Optional identifier appended to the
     *                                        User-Agent by wrapping packages
     *                                        (e.g. "lettr-laravel/2.2.0").
     * @param  RetryConfig|null  $retry  How to handle the rate limit. Defaults to
     *                                   {@see RetryConfig::default()}: up to 3 retries
     *                                   of a rate-limit 429, each waiting at most
     *                                   5 seconds. Pass {@see RetryConfig::disabled()}
     *                                   to throw on the first 429.
     */
    public function __construct(string $apiKey, ?string $userAgentSuffix = null, ?RetryConfig $retry = null)
    {
        $this->apiKey = $apiKey;
        $this->retry = $retry;
        // Strip control characters (incl. CR/LF) so a caller-supplied suffix
        // can't inject extra headers, then trim surrounding whitespace.
        $suffix = $userAgentSuffix !== null
            ? trim((string) preg_replace('/[\x00-\x1F\x7F]/', '', $userAgentSuffix))
            : '';
        $this->userAgent = 'lettr-php/'.Lettr::VERSION
            .($suffix !== '' ? ' '.$suffix : '');
        $this->httpClient = new GuzzleClient([
            'base_uri' => Lettr::BASE_URL,
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function post(string $uri, array $data): array
    {
        return $this->request('POST', $uri, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function postWithHeaders(string $uri, array $data, array $headers): array
    {
        return $this->request('POST', $uri, $data, headers: $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function postExpectingEnvelope(string $uri, ?array $data = null): array
    {
        return $this->request('POST', $uri, $data, null, unwrapEnvelope: false);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $uri): array
    {
        return $this->request('GET', $uri);
    }

    /**
     * {@inheritDoc}
     */
    public function getWithQuery(string $uri, array $query = []): array
    {
        return $this->request('GET', $uri, null, $query);
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $uri, array $data): array
    {
        return $this->request('PUT', $uri, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function patch(string $uri, array $data): array
    {
        return $this->request('PATCH', $uri, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $uri): void
    {
        $this->request('DELETE', $uri);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteReturningBody(string $uri): array
    {
        return $this->request('DELETE', $uri);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteWithBody(string $uri, array $data): array
    {
        return $this->request('DELETE', $uri, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function lastResponseHeaders(): array
    {
        return $this->lastHeaders;
    }

    /**
     * {@inheritDoc}
     */
    public function lastStatusCode(): ?int
    {
        return $this->lastStatusCode;
    }

    /**
     * Send a request to the API.
     *
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>|null  $query
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     *
     * @throws ApiException|TransporterException
     */
    private function request(
        string $method,
        string $uri,
        ?array $data = null,
        ?array $query = null,
        bool $unwrapEnvelope = true,
        array $headers = [],
    ): array {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => $this->userAgent,
                ...$headers,
            ],
        ];

        if ($data !== null) {
            $options['json'] = $data;
        }

        if ($query !== null && count($query) > 0) {
            $options['query'] = $query;
        }

        try {
            $response = $this->send($method, $uri, $options);
            $this->lastHeaders = $this->extractHeaders($response);
            $this->lastStatusCode = $response->getStatusCode();
            $contents = $response->getBody()->getContents();

            if (trim($contents) === '') {
                return [];
            }

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            // Unwrap data envelope if present (skipped when the caller needs
            // to distinguish "data omitted" from "data is the payload" —
            // e.g. campaign action endpoints).
            if ($unwrapEnvelope && isset($decoded['data']) && is_array($decoded['data'])) {
                return $decoded['data'];
            }

            return $decoded;
        } catch (GuzzleException $e) {
            $this->handleGuzzleException($e);
        } catch (JsonException $e) {
            throw new TransporterException('Failed to decode API response: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Send the request, waiting out the rate-limit window first and retrying
     * rate-limit 429s as {@see RetryConfig} allows.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws GuzzleException when the request fails and is not retried.
     */
    private function send(string $method, string $uri, array $options): ResponseInterface
    {
        $attempt = 0;

        while (true) {
            $this->waitForRateLimitWindow();

            try {
                $response = $this->httpClient->request($method, $uri, $options);
            } catch (GuzzleException $e) {
                $delay = $this->retryDelay($e, $attempt);

                if ($delay === null) {
                    throw $e;
                }

                $attempt++;
                $this->sleep($delay);

                continue;
            }

            $this->rememberRateLimit($response);

            return $response;
        }
    }

    /**
     * How long to wait before retrying, or null when the failure must surface.
     */
    private function retryDelay(GuzzleException $e, int $attempt): ?float
    {
        $config = $this->retryConfig();

        if ($attempt >= $config->maxRetries || ! method_exists($e, 'getResponse')) {
            return null;
        }

        $response = $e->getResponse();

        if (! $response instanceof ResponseInterface || $response->getStatusCode() !== 429) {
            return null;
        }

        // Read the body to tell quota from rate limit, then rewind it so
        // handleGuzzleException() can read it again if this is not retried.
        $body = $response->getBody();
        $contents = (string) $body;
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $decoded = json_decode($contents, true);
        $errorCode = is_array($decoded) ? ($decoded['error_code'] ?? null) : null;

        if (in_array($errorCode, self::QUOTA_ERROR_CODES, true)) {
            return null;
        }

        $retryAfter = $this->parseRetryAfter($response->getHeaderLine('Retry-After'));

        if ($retryAfter === null) {
            // No hint from the API: back off 0.5s, 1s, 2s, ...
            return min(0.5 * (2 ** $attempt), (float) $config->maxWaitSeconds);
        }

        // The API wants a longer pause than the caller is willing to block
        // for; let the caller's own queue or backoff handle it.
        if ($retryAfter > $config->maxWaitSeconds) {
            return null;
        }

        return $retryAfter;
    }

    /**
     * Retry-After is either a number of seconds or an HTTP date.
     */
    private function parseRetryAfter(string $value): ?float
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return max(0.0, (float) $value);
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : max(0.0, $timestamp - $this->now());
    }

    /**
     * When a response says the window has no requests left, note when the
     * next request may go out.
     *
     * X-RateLimit-Reset is the Unix second at which the window resets. The
     * wait is measured against the local clock, but never longer than the
     * server's own Date header implies, so a local clock running behind
     * cannot cause long sleeps.
     */
    private function rememberRateLimit(ResponseInterface $response): void
    {
        $config = $this->retryConfig();
        $this->pauseUntil = null;

        if (! $config->pace) {
            return;
        }

        $rateLimit = RateLimit::fromHeaders($this->extractHeaders($response));

        if ($rateLimit === null || $rateLimit->remaining > 0 || $rateLimit->reset <= 0) {
            return;
        }

        $now = $this->now();
        $wait = $rateLimit->reset - $now;

        $serverNow = strtotime($response->getHeaderLine('Date'));
        if ($serverNow !== false) {
            $wait = min($wait, (float) ($rateLimit->reset - $serverNow));
        }

        $wait = min($wait, (float) $config->maxWaitSeconds);

        if ($wait > 0) {
            $this->pauseUntil = $now + $wait;
        }
    }

    private function waitForRateLimitWindow(): void
    {
        if ($this->pauseUntil === null) {
            return;
        }

        $wait = $this->pauseUntil - $this->now();
        $this->pauseUntil = null;

        if ($wait > 0) {
            $this->sleep($wait);
        }
    }

    private function retryConfig(): RetryConfig
    {
        return $this->retry ??= RetryConfig::default();
    }

    private function sleep(float $seconds): void
    {
        if ($this->sleeper !== null) {
            ($this->sleeper)($seconds);

            return;
        }

        usleep((int) round($seconds * 1_000_000));
    }

    private function now(): float
    {
        return $this->clock !== null ? ($this->clock)() : microtime(true);
    }

    /**
     * Handle Guzzle exceptions and convert them to Lettr exceptions.
     *
     * @throws ApiException|TransporterException
     */
    private function handleGuzzleException(GuzzleException $e): never
    {
        if (method_exists($e, 'getResponse') && $e->getResponse() !== null) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $contents = $response->getBody()->getContents();

            try {
                /** @var array{message?: string, error?: string, error_code?: string, errors?: array<string, array<string>>} $body */
                $body = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                $message = $body['message'] ?? $body['error'] ?? 'Unknown API error';
            } catch (JsonException) {
                $message = $contents ?: 'Unknown API error';
            }

            $errorCode = $body['error_code'] ?? null;

            match ($statusCode) {
                401 => throw new UnauthorizedException($message, $e, $errorCode),
                403 => throw new ForbiddenException($message, $e, $errorCode),
                404 => throw new NotFoundException($message, $e, $errorCode),
                409 => $this->handleConflict($response, $message, $e, $errorCode),
                422 => throw new ValidationException(
                    $message,
                    /** @var array<string, array<string>> */
                    $body['errors'] ?? [],
                    $e,
                    $errorCode,
                ),
                429 => $this->handleRateLimitOrQuota($body ?? [], $response, $message, $e),
                default => throw new ApiException($message, $statusCode, $e, $errorCode),
            };
        }

        throw new TransporterException($e->getMessage(), (int) $e->getCode(), $e);
    }

    /**
     * Handle 409 responses.
     *
     * The two idempotency conflicts need telling apart, because one is
     * retryable and the other is not: `idempotency_in_progress` means the
     * original send is still running and should be retried with the *same*
     * key, while `idempotency_key_conflict` means that key was already used
     * for a different payload and retrying will fail forever.
     *
     * @throws ConflictException
     */
    private function handleConflict(ResponseInterface $response, string $message, GuzzleException $e, ?string $errorCode): never
    {
        if ($errorCode === 'idempotency_in_progress') {
            $headers = $this->extractHeaders($response);
            $retryAfter = isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null;

            throw new IdempotencyInProgressException($message, $retryAfter, $e, $errorCode);
        }

        if ($errorCode === 'idempotency_key_conflict') {
            throw new IdempotencyConflictException($message, $e, $errorCode);
        }

        throw new ConflictException($message, $e, $errorCode);
    }

    /**
     * Handle 429 responses — either quota exceeded or rate limited.
     *
     * @param  array{error_code?: string}  $body
     *
     * @throws QuotaExceededException|RateLimitException
     */
    private function handleRateLimitOrQuota(array $body, ResponseInterface $response, string $message, GuzzleException $e): never
    {
        $headers = $this->extractHeaders($response);
        $errorCode = $body['error_code'] ?? null;

        if ($errorCode === 'quota_exceeded' || $errorCode === 'daily_quota_exceeded') {
            throw new QuotaExceededException($message, SendingQuota::fromHeaders($headers), $e, $errorCode);
        }

        $rateLimit = RateLimit::fromHeaders($headers);
        $retryAfter = isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null;

        throw new RateLimitException($message, $rateLimit, $retryAfter, $e, $errorCode);
    }

    /**
     * Extract headers from a PSR-7 response into a flat array.
     *
     * @return array<string, string|string[]>
     */
    private function extractHeaders(ResponseInterface $response): array
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = count($values) === 1 ? $values[0] : $values;
        }

        return $headers;
    }
}
