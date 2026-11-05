<?php

declare(strict_types=1);

namespace Tests\Support;

use Lettr\Contracts\TransporterContract;
use RuntimeException;
use Throwable;

/**
 * Transporter that records every call and answers from a queue, so tests can
 * assert on multi-request flows such as chunked bulk calls.
 *
 * Queue entries are response payloads, or a Throwable to raise for that call.
 */
final class SequenceTransporter implements TransporterContract
{
    /** @var array<int, array{method: string, uri: string, data: array<string, mixed>|null}> */
    public array $calls = [];

    /**
     * @param  array<int, array<string, mixed>|Throwable>  $queue
     */
    public function __construct(private array $queue = []) {}

    public function post(string $uri, array $data): array
    {
        return $this->answer('POST', $uri, $data);
    }

    public function postExpectingEnvelope(string $uri, ?array $data = null): array
    {
        return $this->answer('POST', $uri, $data);
    }

    public function get(string $uri): array
    {
        return $this->answer('GET', $uri, null);
    }

    public function getWithQuery(string $uri, array $query = []): array
    {
        return $this->answer('GET', $uri, null);
    }

    public function put(string $uri, array $data): array
    {
        return $this->answer('PUT', $uri, $data);
    }

    public function patch(string $uri, array $data): array
    {
        return $this->answer('PATCH', $uri, $data);
    }

    public function delete(string $uri): void
    {
        $this->answer('DELETE', $uri, null);
    }

    public function deleteWithBody(string $uri, array $data): array
    {
        return $this->answer('DELETE', $uri, $data);
    }

    public function lastResponseHeaders(): array
    {
        return [];
    }

    public function lastStatusCode(): ?int
    {
        return null;
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return array<string, mixed>
     */
    private function answer(string $method, string $uri, ?array $data): array
    {
        $this->calls[] = ['method' => $method, 'uri' => $uri, 'data' => $data];

        $next = array_shift($this->queue) ?? throw new RuntimeException('SequenceTransporter queue is empty.');

        if ($next instanceof Throwable) {
            throw $next;
        }

        return $next;
    }
}
