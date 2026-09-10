<?php

declare(strict_types=1);

namespace Lettr\Exceptions;

/**
 * The original request for this `Idempotency-Key` is still being processed
 * (HTTP 409, `idempotency_in_progress`).
 *
 * Unlike {@see IdempotencyConflictException} this **is** retryable, and must be
 * retried with the *same* key - a fresh key would send a second email. Wait
 * `$retryAfter` seconds first.
 */
final class IdempotencyInProgressException extends ConflictException
{
    /**
     * @param  int|null  $retryAfter  Seconds to wait, from the `Retry-After` header.
     */
    public function __construct(
        string $message = 'A request with this Idempotency-Key is still processing. Retry with the same key.',
        public readonly ?int $retryAfter = null,
        ?\Throwable $previous = null,
        ?string $errorCode = null,
    ) {
        parent::__construct($message, $previous, $errorCode);
    }
}
