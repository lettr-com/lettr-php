<?php

declare(strict_types=1);

namespace Lettr;

use Lettr\Exceptions\InvalidValueException;

/**
 * How the client reacts to the API's rate limit.
 *
 * Only rate-limit 429s are retried. They are rejected by the API before any
 * work is done, so a retry can never send an email or create a contact twice.
 * Quota 429s, validation errors, 5xx responses and timeouts are never retried.
 *
 * Retries are on by default. Pass {@see self::disabled()} to get the old
 * fail-fast behaviour.
 */
final readonly class RetryConfig
{
    /**
     * @param  int  $maxRetries  Retries after the first attempt. 0 disables retrying.
     * @param  int  $maxWaitSeconds  Longest single wait. A 429 asking for longer is thrown
     *                               immediately, and pacing never sleeps longer than this.
     * @param  bool  $pace  When a response reports no requests left in the current window,
     *                      wait for the window to reset before sending the next request.
     *
     * @throws InvalidValueException when a value is negative.
     */
    public function __construct(
        public int $maxRetries = 3,
        public int $maxWaitSeconds = 5,
        public bool $pace = true,
    ) {
        if ($maxRetries < 0) {
            throw new InvalidValueException('maxRetries cannot be negative.');
        }

        if ($maxWaitSeconds < 0) {
            throw new InvalidValueException('maxWaitSeconds cannot be negative.');
        }
    }

    public static function default(): self
    {
        return new self;
    }

    /**
     * No retries and no pacing: a 429 throws {@see Exceptions\RateLimitException} at once.
     */
    public static function disabled(): self
    {
        return new self(maxRetries: 0, maxWaitSeconds: 0, pace: false);
    }
}
