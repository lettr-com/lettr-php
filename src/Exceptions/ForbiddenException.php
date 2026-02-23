<?php

declare(strict_types=1);

namespace Lettr\Exceptions;

/**
 * Exception thrown when the API key lacks required permissions (insufficient scope).
 */
final class ForbiddenException extends ApiException
{
    public function __construct(string $message = 'Your API key does not have the required permissions for this action.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
