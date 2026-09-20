<?php

declare(strict_types=1);

namespace Lettr\Contracts;

use Lettr\Exceptions\LettrException;

/**
 * A transporter that can return the body of a DELETE response.
 *
 * {@see TransporterContract::delete()} returns void, and widening it would
 * break every class implementing that interface — the same reasoning as
 * {@see SupportsRequestHeaders}. Some endpoints answer a DELETE with the
 * resource they just changed: cancelling a scheduled email returns that email
 * with `state: cancelled`.
 *
 * A transporter that does not implement this keeps working untouched; callers
 * fall back to `delete()` and fetch the resource separately, which costs one
 * extra request.
 */
interface SupportsDeleteWithResponse
{
    /**
     * Send a DELETE request and return the decoded response body.
     *
     * A top-level `data` envelope is unwrapped, as with the other verbs.
     *
     * @return array<string, mixed>
     *
     * @throws LettrException
     */
    public function deleteReturningBody(string $uri): array;
}
