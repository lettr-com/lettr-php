<?php

declare(strict_types=1);

namespace Lettr\Contracts;

use Lettr\Exceptions\LettrException;

interface TransporterContract
{
    /**
     * Send a POST request to the API.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws LettrException
     */
    public function post(string $uri, array $data): array;

    /**
     * Send a GET request to the API.
     *
     * @return array<string, mixed>
     *
     * @throws LettrException
     */
    public function get(string $uri): array;

    /**
     * Send a GET request with query parameters to the API.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws LettrException
     */
    public function getWithQuery(string $uri, array $query = []): array;

    /**
     * Send a PUT request to the API.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws LettrException
     */
    public function put(string $uri, array $data): array;

    /**
     * Send a PATCH request to the API.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws LettrException
     */
    public function patch(string $uri, array $data): array;

    /**
     * Send a DELETE request to the API.
     *
     * @throws LettrException
     */
    public function delete(string $uri): void;

    /**
     * Send a DELETE request with a JSON body and a non-empty response.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws LettrException
     */
    public function deleteWithBody(string $uri, array $data): array;

    /**
     * Get the response headers from the last request.
     *
     * @return array<string, string|string[]>
     */
    public function lastResponseHeaders(): array;

    /**
     * Get the HTTP status code from the last successful API response.
     */
    public function lastStatusCode(): ?int;
}
