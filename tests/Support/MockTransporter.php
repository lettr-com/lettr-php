<?php

declare(strict_types=1);

namespace Tests\Support;

use Lettr\Contracts\TransporterContract;

/**
 * Shared mock transporter for service tests.
 *
 * Tracks the URI, body, and query string of the last call, and returns a
 * configurable response payload plus optional response headers.
 */
final class MockTransporter implements TransporterContract
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

    public ?int $statusCode = null;

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
