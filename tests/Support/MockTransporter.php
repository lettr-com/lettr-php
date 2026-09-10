<?php

declare(strict_types=1);

namespace Tests\Support;

use Lettr\Contracts\SupportsRequestHeaders;
use Lettr\Contracts\TransporterContract;
use Throwable;

/**
 * Shared mock transporter for service tests.
 *
 * Tracks the URI, body, and query string of the last call, and returns a
 * configurable response payload plus optional response headers. Set `$throws`
 * to make every request raise that exception instead, for testing how services
 * translate API errors.
 */
final class MockTransporter implements SupportsRequestHeaders, TransporterContract
{
    public ?Throwable $throws = null;

    public ?string $lastUri = null;

    /** @var array<string, mixed>|null */
    public ?array $lastData = null;

    /** @var array<string, mixed>|null */
    public ?array $lastQuery = null;

    /** @var array<string, string> */
    public array $lastHeaders = [];

    /** @var array<string, mixed> */
    public array $response = [];

    /** @var array<string, string|string[]> */
    public array $responseHeaders = [];

    public ?int $statusCode = null;

    public function post(string $uri, array $data): array
    {
        $this->lastUri = $uri;
        $this->lastData = $data;

        if ($this->throws !== null) {
            throw $this->throws;
        }

        return $this->response;
    }

    public function postWithHeaders(string $uri, array $data, array $headers): array
    {
        $this->lastHeaders = $headers;

        return $this->post($uri, $data);
    }

    public function postExpectingEnvelope(string $uri, ?array $data = null): array
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

    public function put(string $uri, array $data): array
    {
        $this->lastUri = $uri;
        $this->lastData = $data;

        return $this->response;
    }

    public function patch(string $uri, array $data): array
    {
        $this->lastUri = $uri;
        $this->lastData = $data;

        return $this->response;
    }

    public function delete(string $uri): void
    {
        $this->lastUri = $uri;
    }

    public function deleteWithBody(string $uri, array $data): array
    {
        $this->lastUri = $uri;
        $this->lastData = $data;

        return $this->response;
    }

    public function lastResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function lastStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
