<?php

declare(strict_types=1);

namespace Lettr\Collections;

use Lettr\Dto\Email\ScheduledEmail;
use Lettr\Enums\ScheduledEmailState;

/**
 * Collection of scheduled emails.
 *
 * @extends Collection<ScheduledEmail>
 */
final readonly class ScheduledEmailCollection extends Collection
{
    /**
     * Get the first scheduled email in the collection.
     */
    public function first(): ?ScheduledEmail
    {
        return $this->items[0] ?? null;
    }

    /**
     * Find a scheduled email by its request ID (`sch_...`).
     */
    public function findByRequestId(string $requestId): ?ScheduledEmail
    {
        foreach ($this->items as $scheduledEmail) {
            if ($scheduledEmail->requestId->value === $requestId) {
                return $scheduledEmail;
            }
        }

        return null;
    }

    /**
     * Filter scheduled emails by state.
     */
    public function filterByState(ScheduledEmailState $state): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (ScheduledEmail $scheduledEmail): bool => $scheduledEmail->state === $state
            )
        );
    }

    /**
     * Only the emails that can still be cancelled.
     */
    public function cancellable(): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn (ScheduledEmail $scheduledEmail): bool => $scheduledEmail->isCancellable()
            )
        );
    }
}
