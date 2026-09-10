<?php

declare(strict_types=1);

namespace Lettr\Exceptions;

/**
 * The `Idempotency-Key` was already used with a *different* request payload
 * (HTTP 409, `idempotency_key_conflict`).
 *
 * This is a bug on the caller's side - two different emails were sent under one
 * key - and it is **not retryable**. Retrying the same request produces the same
 * 409 forever. Either use a key that is unique per logical send, or send the
 * payload the key was first used with.
 *
 * Note keys are scoped per team **and** API key, so the same string sent through
 * a different API key is a different key and will not collide.
 */
final class IdempotencyConflictException extends ConflictException
{
    public function __construct(
        string $message = 'This Idempotency-Key was already used with a different request payload.',
        ?\Throwable $previous = null,
        ?string $errorCode = null,
    ) {
        parent::__construct($message, $previous, $errorCode);
    }
}
