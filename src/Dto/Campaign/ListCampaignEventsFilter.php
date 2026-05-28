<?php

declare(strict_types=1);

namespace Lettr\Dto\Campaign;

use DateTimeInterface;
use Lettr\Contracts\Arrayable;
use Lettr\Enums\EventType;

/**
 * Filter for the cursor-paginated campaign events endpoint.
 *
 * `$eventType` is constrained to {@see EventType} for the call site, even
 * though the campaigns endpoint only accepts the seven engagement-subset
 * values (`injection`, `delivery`, `bounce`, `spam_complaint`, `open`,
 * `click`, `list_unsubscribe`). Passing any other value returns a 422 from
 * the server.
 */
final readonly class ListCampaignEventsFilter implements Arrayable
{
    public function __construct(
        public ?EventType $eventType = null,
        public ?string $email = null,
        public DateTimeInterface|string|null $startDate = null,
        public DateTimeInterface|string|null $endDate = null,
        public ?int $limit = null,
        public ?string $cursor = null,
    ) {}

    public static function create(): self
    {
        return new self;
    }

    public function eventType(EventType $eventType): self
    {
        return new self($eventType, $this->email, $this->startDate, $this->endDate, $this->limit, $this->cursor);
    }

    public function email(string $email): self
    {
        return new self($this->eventType, $email, $this->startDate, $this->endDate, $this->limit, $this->cursor);
    }

    public function startDate(DateTimeInterface|string $startDate): self
    {
        return new self($this->eventType, $this->email, $startDate, $this->endDate, $this->limit, $this->cursor);
    }

    public function endDate(DateTimeInterface|string $endDate): self
    {
        return new self($this->eventType, $this->email, $this->startDate, $endDate, $this->limit, $this->cursor);
    }

    public function limit(int $limit): self
    {
        return new self($this->eventType, $this->email, $this->startDate, $this->endDate, $limit, $this->cursor);
    }

    /**
     * Set the pagination cursor. Pass `null` (or omit) on the first request;
     * pass the previous response's `nextCursor` on subsequent requests.
     */
    public function cursor(?string $cursor): self
    {
        return new self($this->eventType, $this->email, $this->startDate, $this->endDate, $this->limit, $cursor);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $params = [];

        if ($this->eventType !== null) {
            $params['event_type'] = $this->eventType->value;
        }

        if ($this->email !== null) {
            $params['email'] = $this->email;
        }

        if ($this->startDate !== null) {
            $params['start_date'] = $this->formatDate($this->startDate);
        }

        if ($this->endDate !== null) {
            $params['end_date'] = $this->formatDate($this->endDate);
        }

        if ($this->limit !== null) {
            $params['limit'] = $this->limit;
        }

        // An empty cursor is meaningless to the API — skip it so callers can
        // safely write `->cursor($previous?->nextCursor)` on the first call.
        if ($this->cursor !== null && $this->cursor !== '') {
            $params['cursor'] = $this->cursor;
        }

        return $params;
    }

    private function formatDate(DateTimeInterface|string $date): string
    {
        return $date instanceof DateTimeInterface
            ? $date->format(DateTimeInterface::ATOM)
            : $date;
    }
}
