<?php

declare(strict_types=1);

namespace Lettr\Services;

use Lettr\Builders\EmailBuilder;
use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Email\SendEmailData;
use Lettr\Dto\Email\SendEmailResponse;
use Lettr\ValueObjects\EmailAddress;

/**
 * Service for sending and managing emails via the Lettr API.
 */
final class EmailService
{
    private const EMAILS_ENDPOINT = 'emails';

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
        ?string $subject, // TODO 2.0.0: make optional by reordering after $templateSlug
        string $templateSlug,
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
}
