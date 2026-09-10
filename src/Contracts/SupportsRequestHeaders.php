<?php

declare(strict_types=1);

namespace Lettr\Contracts;

use Lettr\Exceptions\LettrException;

/**
 * A transporter that can send per-request headers.
 *
 * Deliberately separate from {@see TransporterContract} rather than an argument
 * on its `post()`: adding a parameter to an interface method breaks every class
 * implementing it, and custom transporters - test doubles, proxies, loggers -
 * are a documented extension point here.
 *
 * A transporter that does not implement this keeps working untouched; callers
 * that need headers check for it and fall back. In practice that means an
 * `Idempotency-Key` passed through a custom transporter is silently not sent,
 * which is the price of not breaking anyone's implementation.
 */
interface SupportsRequestHeaders
{
    /**
     * Send a POST request with extra headers merged over the defaults.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     *
     * @throws LettrException
     */
    public function postWithHeaders(string $uri, array $data, array $headers): array;
}
