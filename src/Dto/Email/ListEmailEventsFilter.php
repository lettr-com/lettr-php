<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

use Lettr\Contracts\Arrayable;

/**
 * Filter parameters for listing email events.
 */
final readonly class ListEmailEventsFilter implements Arrayable
{
    /**
     * @param  array<string>|null  $events
     * @param  array<string>|null  $recipients
     */
    public function __construct(
        public ?array $events = null,
        public ?array $recipients = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?int $perPage = null,
        public ?string $cursor = null,
        public ?string $transmissions = null,
        public ?string $bounceClasses = null,
    ) {}

    /**
     * Create a new filter.
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Set the event types filter.
     *
     * @param  array<string>  $events
     */
    public function events(array $events): self
    {
        return new self(
            events: $events,
            recipients: $this->recipients,
            from: $this->from,
            to: $this->to,
            perPage: $this->perPage,
            cursor: $this->cursor,
            transmissions: $this->transmissions,
            bounceClasses: $this->bounceClasses,
        );
    }

    /**
     * Set the recipients filter.
     *
     * @param  array<string>  $recipients
     */
    public function recipients(array $recipients): self
    {
        return new self(
            events: $this->events,
            recipients: $recipients,
            from: $this->from,
            to: $this->to,
            perPage: $this->perPage,
            cursor: $this->cursor,
            transmissions: $this->transmissions,
            bounceClasses: $this->bounceClasses,
        );
    }

    /**
     * Set the from date filter (ISO 8601).
     */
    public function from(string $from): self
    {
        return new self(
            events: $this->events,
            recipients: $this->recipients,
            from: $from,
            to: $this->to,
            perPage: $this->perPage,
            cursor: $this->cursor,
            transmissions: $this->transmissions,
            bounceClasses: $this->bounceClasses,
        );
    }

    /**
     * Set the to date filter (ISO 8601).
     */
    public function to(string $to): self
    {
        return new self(
            events: $this->events,
            recipients: $this->recipients,
            from: $this->from,
            to: $to,
            perPage: $this->perPage,
            cursor: $this->cursor,
            transmissions: $this->transmissions,
            bounceClasses: $this->bounceClasses,
        );
    }

    /**
     * Set items per page.
     */
    public function perPage(int $perPage): self
    {
        return new self(
            events: $this->events,
            recipients: $this->recipients,
            from: $this->from,
            to: $this->to,
            perPage: $perPage,
            cursor: $this->cursor,
            transmissions: $this->transmissions,
            bounceClasses: $this->bounceClasses,
        );
    }

    /**
     * Set the cursor.
     */
    public function cursor(string $cursor): self
    {
        return new self(
            events: $this->events,
            recipients: $this->recipients,
            from: $this->from,
            to: $this->to,
            perPage: $this->perPage,
            cursor: $cursor,
            transmissions: $this->transmissions,
            bounceClasses: $this->bounceClasses,
        );
    }

    /**
     * Set the transmissions filter.
     */
    public function transmissions(string $transmissions): self
    {
        return new self(
            events: $this->events,
            recipients: $this->recipients,
            from: $this->from,
            to: $this->to,
            perPage: $this->perPage,
            cursor: $this->cursor,
            transmissions: $transmissions,
            bounceClasses: $this->bounceClasses,
        );
    }

    /**
     * Set the bounce classes filter (comma-separated).
     */
    public function bounceClasses(string $bounceClasses): self
    {
        return new self(
            events: $this->events,
            recipients: $this->recipients,
            from: $this->from,
            to: $this->to,
            perPage: $this->perPage,
            cursor: $this->cursor,
            transmissions: $this->transmissions,
            bounceClasses: $bounceClasses,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $params = [];

        if ($this->events !== null) {
            $params['events'] = implode(',', $this->events);
        }

        if ($this->recipients !== null) {
            $params['recipients'] = implode(',', $this->recipients);
        }

        if ($this->from !== null) {
            $params['from'] = $this->from;
        }

        if ($this->to !== null) {
            $params['to'] = $this->to;
        }

        if ($this->perPage !== null) {
            $params['per_page'] = $this->perPage;
        }

        if ($this->cursor !== null) {
            $params['cursor'] = $this->cursor;
        }

        if ($this->transmissions !== null) {
            $params['transmissions'] = $this->transmissions;
        }

        if ($this->bounceClasses !== null) {
            $params['bounce_classes'] = $this->bounceClasses;
        }

        return $params;
    }

    /**
     * Check if any filters are set.
     */
    public function hasFilters(): bool
    {
        return $this->events !== null
            || $this->recipients !== null
            || $this->from !== null
            || $this->to !== null
            || $this->perPage !== null
            || $this->cursor !== null
            || $this->transmissions !== null
            || $this->bounceClasses !== null;
    }
}
