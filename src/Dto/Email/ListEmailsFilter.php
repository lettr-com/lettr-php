<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

use Lettr\Contracts\Arrayable;

/**
 * Filter parameters for listing emails.
 */
final readonly class ListEmailsFilter implements Arrayable
{
    public function __construct(
        public ?int $perPage = null,
        public ?string $cursor = null,
        public ?string $recipients = null,
        public ?string $from = null,
        public ?string $to = null,
    ) {}

    /**
     * Create a new filter.
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Set items per page.
     */
    public function perPage(int $perPage): self
    {
        return new self(
            perPage: $perPage,
            cursor: $this->cursor,
            recipients: $this->recipients,
            from: $this->from,
            to: $this->to,
        );
    }

    /**
     * Set the cursor.
     */
    public function cursor(string $cursor): self
    {
        return new self(
            perPage: $this->perPage,
            cursor: $cursor,
            recipients: $this->recipients,
            from: $this->from,
            to: $this->to,
        );
    }

    /**
     * Set the recipients filter.
     */
    public function recipients(string $recipients): self
    {
        return new self(
            perPage: $this->perPage,
            cursor: $this->cursor,
            recipients: $recipients,
            from: $this->from,
            to: $this->to,
        );
    }

    /**
     * Set the from date filter (ISO 8601).
     */
    public function from(string $from): self
    {
        return new self(
            perPage: $this->perPage,
            cursor: $this->cursor,
            recipients: $this->recipients,
            from: $from,
            to: $this->to,
        );
    }

    /**
     * Set the to date filter (ISO 8601).
     */
    public function to(string $to): self
    {
        return new self(
            perPage: $this->perPage,
            cursor: $this->cursor,
            recipients: $this->recipients,
            from: $this->from,
            to: $to,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $params = [];

        if ($this->perPage !== null) {
            $params['per_page'] = $this->perPage;
        }

        if ($this->cursor !== null) {
            $params['cursor'] = $this->cursor;
        }

        if ($this->recipients !== null) {
            $params['recipients'] = $this->recipients;
        }

        if ($this->from !== null) {
            $params['from'] = $this->from;
        }

        if ($this->to !== null) {
            $params['to'] = $this->to;
        }

        return $params;
    }

    /**
     * Check if any filters are set.
     */
    public function hasFilters(): bool
    {
        return $this->perPage !== null
            || $this->cursor !== null
            || $this->recipients !== null
            || $this->from !== null
            || $this->to !== null;
    }
}
