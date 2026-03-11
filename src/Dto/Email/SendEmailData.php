<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

use Lettr\Collections\AttachmentCollection;
use Lettr\Collections\EmailAddressCollection;
use Lettr\Contracts\Arrayable;
use Lettr\ValueObjects\EmailAddress;
use Lettr\ValueObjects\Subject;
use Lettr\ValueObjects\Tag;

/**
 * Data Transfer Object for sending an email.
 */
final readonly class SendEmailData implements Arrayable
{
    public function __construct(
        public EmailAddress $from,
        public EmailAddressCollection $to,
        public ?Subject $subject = null,
        public ?string $text = null,
        public ?string $html = null,
        public ?EmailAddressCollection $cc = null,
        public ?EmailAddressCollection $bcc = null,
        public ?EmailAddress $replyTo = null,
        public ?AttachmentCollection $attachments = null,
        public ?EmailOptions $options = null,
        public ?Metadata $metadata = null,
        public ?SubstitutionData $substitutionData = null,
        public ?Tag $tag = null,
        public ?int $projectId = null,
        public ?string $templateSlug = null,
        public ?int $templateVersion = null,
    ) {}

    /**
     * Create a new instance from an array.
     *
     * @param  array{
     *     from: string|array{email: string, name?: string},
     *     to: array<string>,
     *     subject?: string|null,
     *     text?: string|null,
     *     html?: string|null,
     *     cc?: array<string>|null,
     *     bcc?: array<string>|null,
     *     reply_to?: string|null,
     *     attachments?: array<array{name: string, type: string, data: string}>|null,
     *     options?: array{click_tracking?: bool, open_tracking?: bool, transactional?: bool, inline_css?: bool, perform_substitutions?: bool}|null,
     *     metadata?: array<string, string>|null,
     *     substitution_data?: array<string, mixed>|null,
     *     tag?: string|null,
     *     project_id?: int|null,
     *     template_slug?: string|null,
     *     template_version?: int|null,
     * }  $data
     */
    public static function from(array $data): self
    {
        $from = is_array($data['from'])
            ? new EmailAddress($data['from']['email'], $data['from']['name'] ?? null)
            : new EmailAddress($data['from']);

        return new self(
            from: $from,
            to: EmailAddressCollection::forRecipients($data['to']),
            subject: isset($data['subject']) ? new Subject($data['subject']) : null,
            text: $data['text'] ?? null,
            html: $data['html'] ?? null,
            cc: isset($data['cc']) ? EmailAddressCollection::from($data['cc']) : null,
            bcc: isset($data['bcc']) ? EmailAddressCollection::from($data['bcc']) : null,
            replyTo: isset($data['reply_to']) ? new EmailAddress($data['reply_to']) : null,
            attachments: isset($data['attachments']) ? AttachmentCollection::from(
                array_map(static fn (array $a): Attachment => Attachment::from($a), $data['attachments'])
            ) : null,
            options: isset($data['options']) ? EmailOptions::from($data['options']) : null,
            metadata: isset($data['metadata']) ? Metadata::from($data['metadata']) : null,
            substitutionData: isset($data['substitution_data']) ? SubstitutionData::from($data['substitution_data']) : null,
            tag: isset($data['tag']) ? new Tag($data['tag']) : null,
            projectId: $data['project_id'] ?? null,
            templateSlug: $data['template_slug'] ?? null,
            templateVersion: $data['template_version'] ?? null,
        );
    }

    /**
     * Convert the DTO to an array for API request.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'from' => $this->from->address,
            'to' => $this->to->toStrings(),
        ];

        if ($this->subject !== null) {
            $data['subject'] = (string) $this->subject;
        }

        if ($this->from->name !== null) {
            $data['from_name'] = $this->from->name;
        }

        if ($this->text !== null) {
            $data['text'] = $this->text;
        }

        if ($this->html !== null) {
            $data['html'] = $this->html;
        }

        if ($this->cc !== null && ! $this->cc->isEmpty()) {
            $data['cc'] = $this->cc->toStrings();
        }

        if ($this->bcc !== null && ! $this->bcc->isEmpty()) {
            $data['bcc'] = $this->bcc->toStrings();
        }

        if ($this->replyTo !== null) {
            $data['reply_to'] = $this->replyTo->address;
        }

        if ($this->attachments !== null && ! $this->attachments->isEmpty()) {
            $data['attachments'] = $this->attachments->toArray();
        }

        if ($this->options !== null) {
            $data['options'] = $this->options->toArray();
        }

        if ($this->metadata !== null && ! $this->metadata->isEmpty()) {
            $data['metadata'] = $this->metadata->toArray();
        }

        if ($this->substitutionData !== null && ! $this->substitutionData->isEmpty()) {
            $data['substitution_data'] = $this->substitutionData->toArray();
        }

        if ($this->tag !== null) {
            $data['tag'] = (string) $this->tag;
        }

        if ($this->projectId !== null) {
            $data['project_id'] = $this->projectId;
        }

        if ($this->templateSlug !== null) {
            $data['template_slug'] = $this->templateSlug;
        }

        if ($this->templateVersion !== null) {
            $data['template_version'] = $this->templateVersion;
        }

        return $data;
    }
}
