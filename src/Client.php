<?php

declare(strict_types=1);

namespace Lettr;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Lettr\Contracts\TransporterContract;
use Lettr\Dto\RateLimit;
use Lettr\Dto\SendingQuota;
use Lettr\Exceptions\ApiException;
use Lettr\Exceptions\ConflictException;
use Lettr\Exceptions\ForbiddenException;
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
final class Client implements TransporterContract
{
    private readonly ClientInterface $httpClient;

    private readonly string $apiKey;

    /** @var array<string, string|string[]> */
    private array $lastHeaders = [];

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
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
    public function delete(string $uri): void
    {
        $this->request('DELETE', $uri);
    }

    /**
     * {@inheritDoc}
     */
    public function lastResponseHeaders(): array
    {
        return $this->lastHeaders;
    }

    /**
     * Send a request to the API.
     *
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>|null  $query
     * @return array<string, mixed>
     *
     * @throws ApiException|TransporterException
     */
    private function request(string $method, string $uri, ?array $data = null, ?array $query = null): array
    {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'lettr-php/'.Lettr::VERSION,
            ],
        ];

        if ($data !== null) {
            $options['json'] = $data;
        }

        if ($query !== null && count($query) > 0) {
            $options['query'] = $query;
        }

        try {
            $response = $this->httpClient->request($method, $uri, $options);
            $this->lastHeaders = $this->extractHeaders($response);
            $contents = $response->getBody()->getContents();

            if (trim($contents) === '') {
                return [];
            }

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            // Unwrap data envelope if present
            if (isset($decoded['data']) && is_array($decoded['data'])) {
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

            match ($statusCode) {
                401 => throw new UnauthorizedException($message, $e),
                403 => throw new ForbiddenException($message, $e),
                404 => throw new NotFoundException($message, $e),
                409 => throw new ConflictException($message, $e),
                422 => throw new ValidationException(
                    $message,
                    /** @var array<string, array<string>> */
                    $body['errors'] ?? [],
                    $e,
                ),
                429 => $this->handleRateLimitOrQuota($body ?? [], $response, $message, $e),
                default => throw new ApiException($message, $statusCode, $e),
            };
        }

        throw new TransporterException($e->getMessage(), (int) $e->getCode(), $e);
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
            throw new QuotaExceededException($message, SendingQuota::fromHeaders($headers), $e);
        }

        $rateLimit = RateLimit::fromHeaders($headers);
        $retryAfter = isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null;

        throw new RateLimitException($message, $rateLimit, $retryAfter, $e);
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
