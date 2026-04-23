<?php

declare(strict_types=1);

namespace Lettr\Services;

use Lettr\Builders\EmailBuilder;
use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Email\ListEmailEventsFilter;
use Lettr\Dto\Email\ListEmailsFilter;
use Lettr\Dto\Email\SendEmailData;
use Lettr\Dto\Email\SendEmailResponse;
use Lettr\Dto\Email\TransmissionDetail;
use Lettr\Responses\ListEmailEventsResponse;
use Lettr\Responses\ListEmailsResponse;
use Lettr\ValueObjects\EmailAddress;
use Lettr\ValueObjects\RequestId;

/**
 * Service for sending and managing emails via the Lettr API.
 */
final class EmailService
{
    private const EMAILS_ENDPOINT = 'emails';

    private const EMAILS_EVENTS_ENDPOINT = 'emails/events';

    private const EMAILS_SCHEDULED_ENDPOINT = 'emails/scheduled';

    public function __construct(
        private readonly TransporterContract $transporter,
    ) {}

    /**
     * Create a new email builder for fluent email construction.
     */
    public function create(): EmailBuilder
    {
        return EmailBuilder::create();
    }

    /**
     * Send an email.
     */
    public function send(SendEmailData|EmailBuilder $data): SendEmailResponse
    {
        $emailData = $data instanceof EmailBuilder ? $data->build() : $data;

        /** @var array{request_id: string, accepted: int, rejected: int} $response */
        $response = $this->transporter->post(self::EMAILS_ENDPOINT, $emailData->toArray());

        return SendEmailResponse::from($response, $this->transporter->lastResponseHeaders());
    }

    /**
     * Send an HTML email.
     *
     * @param  array<string>|string  $to
     * @param  array<string, mixed>|null  $substitutionData
     */
    public function sendHtml(
        string|EmailAddress $from,
        array|string $to,
        string $subject,
        string $html,
        ?array $substitutionData = null,
    ): SendEmailResponse {
        $from = EmailAddress::from($from);

        $builder = $this->create()
            ->from($from->address, $from->name)
            ->to(is_array($to) ? $to : [$to])
            ->subject($subject)
            ->html($html);

        if ($substitutionData !== null) {
            $builder->substitutionData($substitutionData);
        }

        return $this->send($builder);
    }

    /**
     * Send a plain text email.
     *
     * @param  array<string>|string  $to
     * @param  array<string, mixed>|null  $substitutionData
     */
    public function sendText(
        string|EmailAddress $from,
        array|string $to,
        string $subject,
        string $text,
        ?array $substitutionData = null,
    ): SendEmailResponse {
        $from = EmailAddress::from($from);

        $builder = $this->create()
            ->from($from->address, $from->name)
            ->to(is_array($to) ? $to : [$to])
            ->subject($subject)
            ->text($text);

        if ($substitutionData !== null) {
            $builder->substitutionData($substitutionData);
        }

        return $this->send($builder);
    }

    /**
     * Send an email using a template.
     *
     * @param  array<string>|string  $to
     * @param  array<string, mixed>|null  $substitutionData
     */
    public function sendTemplate(
        string|EmailAddress $from,
        array|string $to,
        string $templateSlug,
        ?string $subject = null,
        ?int $templateVersion = null,
        ?int $projectId = null,
        ?array $substitutionData = null,
    ): SendEmailResponse {
        $from = EmailAddress::from($from);

        $builder = $this->create()
            ->from($from->address, $from->name)
            ->to(is_array($to) ? $to : [$to])
            ->useTemplate($templateSlug, $templateVersion, $projectId);

        if ($subject !== null) {
            $builder->subject($subject);
        }

        if ($substitutionData !== null) {
            $builder->substitutionData($substitutionData);
        }

        return $this->send($builder);
    }

    /**
     * List sent emails.
     */
    public function list(?ListEmailsFilter $filter = null): ListEmailsResponse
    {
        $query = $filter !== null ? $filter->toArray() : [];

        /** @var array{events: array{data: array<int, array<string, mixed>>, total_count: int, from: string, to: string, pagination: array{next_cursor: string|null, per_page: int}}} $response */
        $response = $this->transporter->getWithQuery(self::EMAILS_ENDPOINT, $query);

        return ListEmailsResponse::from($response);
    }

    /**
     * List email events.
     */
    public function events(?ListEmailEventsFilter $filter = null): ListEmailEventsResponse
    {
        $query = $filter !== null ? $filter->toArray() : [];

        /** @var array{events: array{data: array<int, array<string, mixed>>, total_count: int, from: string, to: string, pagination: array{next_cursor: string|null, per_page: int}}} $response */
        $response = $this->transporter->getWithQuery(self::EMAILS_EVENTS_ENDPOINT, $query);

        return ListEmailEventsResponse::from($response);
    }

    /**
     * Get detail of a single sent email by request ID.
     *
     * Returns a `TransmissionDetail` — per the spec, `getEmailDetail`
     * shares the same response shape as `showScheduledEmail`.
     */
    public function find(
        string|RequestId $requestId,
        ?string $from = null,
        ?string $to = null,
    ): TransmissionDetail {
        $requestId = $requestId instanceof RequestId ? $requestId->value : $requestId;

        $query = [];
        if ($from !== null) {
            $query['from'] = $from;
        }
        if ($to !== null) {
            $query['to'] = $to;
        }

        /** @var array<string, mixed> $response */
        $response = $query === []
            ? $this->transporter->get(self::EMAILS_ENDPOINT.'/'.$requestId)
            : $this->transporter->getWithQuery(self::EMAILS_ENDPOINT.'/'.$requestId, $query);

        return TransmissionDetail::from($response);
    }

    /**
     * Schedule an email for later delivery.
     */
    public function schedule(SendEmailData|EmailBuilder $data): SendEmailResponse
    {
        $emailData = $data instanceof EmailBuilder ? $data->build() : $data;

        /** @var array{request_id: string, accepted: int, rejected: int} $response */
        $response = $this->transporter->post(self::EMAILS_SCHEDULED_ENDPOINT, $emailData->toArray());

        return SendEmailResponse::from($response, $this->transporter->lastResponseHeaders());
    }

    /**
     * Get a scheduled transmission by ID.
     */
    public function getScheduled(string $transmissionId): TransmissionDetail
    {
        /** @var array<string, mixed> $response */
        $response = $this->transporter->get(self::EMAILS_SCHEDULED_ENDPOINT.'/'.$transmissionId);

        return TransmissionDetail::from($response);
    }

    /**
     * Cancel a scheduled transmission.
     */
    public function cancelScheduled(string $transmissionId): void
    {
        $this->transporter->delete(self::EMAILS_SCHEDULED_ENDPOINT.'/'.$transmissionId);
    }
}
