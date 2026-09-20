<?php

declare(strict_types=1);

namespace Lettr\Responses;

use Lettr\Collections\ScheduledEmailCollection;
use Lettr\Dto\Email\ScheduledEmail;

/**
 * Response from listing scheduled emails.
 */
final readonly class ListScheduledEmailsResponse
{
    public function __construct(
        public ScheduledEmailCollection $scheduledEmails,
        public ScheduledEmailPagination $pagination,
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array{
     *     scheduled_emails: array<int, array<string, mixed>>,
     *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            scheduledEmails: ScheduledEmailCollection::from(
                array_map(
                    static fn (array $scheduledEmail): ScheduledEmail => ScheduledEmail::from($scheduledEmail),
                    $data['scheduled_emails']
                )
            ),
            pagination: ScheduledEmailPagination::from($data['pagination']),
        );
    }

    /**
     * Check if there are more pages.
     */
    public function hasMore(): bool
    {
        return $this->pagination->hasNextPage();
    }
}
