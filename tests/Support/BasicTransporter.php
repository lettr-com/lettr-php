<?php

declare(strict_types=1);

namespace Tests\Support;

use Lettr\Contracts\TransporterContract;

/**
 * A transporter that implements only {@see TransporterContract} — no optional
 * capability interfaces.
 *
 * Stands in for a custom transporter written against the documented contract,
 * so the fallbacks taken for those are covered.
 */
final class BasicTransporter implements TransporterContract
{
    /** @var array<string, mixed> */
    public array $response = [];

    /** @var list<string> */
    public array $calls = [];

    public string $lastUri = '';

    public function post(string $uri, array $data): array
    {
        return $this->record('POST '.$uri, $uri);
    }

    public function postExpectingEnvelope(string $uri, ?array $data = null): array
    {
        return $this->record('POST '.$uri, $uri);
    }

    public function get(string $uri): array
    {
        return $this->record('GET '.$uri, $uri);
    }

    public function getWithQuery(string $uri, array $query = []): array
    {
        return $this->record('GET '.$uri, $uri);
    }

    public function put(string $uri, array $data): array
    {
        return $this->record('PUT '.$uri, $uri);
    }

    public function patch(string $uri, array $data): array
    {
        return $this->record('PATCH '.$uri, $uri);
    }

    public function delete(string $uri): void
    {
        $this->record('DELETE '.$uri, $uri);
    }

    public function deleteWithBody(string $uri, array $data): array
    {
        return $this->record('DELETE '.$uri, $uri);
    }

    public function lastResponseHeaders(): array
    {
        return [];
    }

    public function lastStatusCode(): ?int
    {
        return 200;
    }

    /**
     * @return array<string, mixed>
     */
    private function record(string $call, string $uri): array
    {
        $this->calls[] = $call;
        $this->lastUri = $uri;

        return $this->response;
    }
}
