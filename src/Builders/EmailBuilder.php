<?php

declare(strict_types=1);

namespace Lettr\Builders;

use Lettr\Collections\AttachmentCollection;
use Lettr\Collections\EmailAddressCollection;
use Lettr\Dto\Email\Attachment;
use Lettr\Dto\Email\EmailOptions;
use Lettr\Dto\Email\Metadata;
use Lettr\Dto\Email\SendEmailData;
use Lettr\Dto\Email\SubstitutionData;
use Lettr\Exceptions\InvalidValueException;
use Lettr\ValueObjects\EmailAddress;
use Lettr\ValueObjects\Subject;
use Lettr\ValueObjects\Tag;

/**
 * Fluent builder for creating email send data.
 */
final class EmailBuilder
{
    private ?EmailAddress $from = null;

    private ?EmailAddressCollection $to = null;

    private ?Subject $subject = null;

    private ?string $text = null;

    private ?string $html = null;

    private ?EmailAddressCollection $cc = null;

    private ?EmailAddressCollection $bcc = null;

    private ?EmailAddress $replyTo = null;

    private ?AttachmentCollection $attachments = null;

    private bool $clickTracking = true;

    private bool $openTracking = true;

    private bool $transactional = false;

    private bool $inlineCss = true;

    private bool $performSubstitutions = true;

    private ?Metadata $metadata = null;

    private ?SubstitutionData $substitutionData = null;

    private ?Tag $tag = null;

    private ?int $projectId = null;

    private ?string $templateSlug = null;

    private ?int $templateVersion = null;

    public function __construct() {}

    /**
     * Create a new builder instance.
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Set the sender.
     */
    public function from(string $email, ?string $name = null): self
    {
        $this->from = new EmailAddress($email, $name);

        return $this;
    }

    /**
     * Set the recipients.
     *
     * @param  array<string|EmailAddress>|string|EmailAddress  $recipients
     */
    public function to(array|string|EmailAddress $recipients): self
    {
        if (! is_array($recipients)) {
            $recipients = [$recipients];
        }

        $this->to = EmailAddressCollection::forRecipients($recipients);

        return $this;
    }

    /**
     * Add a recipient.
     */
    public function addTo(string|EmailAddress $email): self
    {
        if ($this->to === null) {
            $this->to = EmailAddressCollection::from([$email]);
        } else {
            $this->to = $this->to->add($email);
        }

        return $this;
    }

    /**
     * Set the CC recipients.
     *
     * @param  array<string|EmailAddress>  $recipients
     */
    public function cc(array $recipients): self
    {
        $this->cc = EmailAddressCollection::from($recipients);

        return $this;
    }

    /**
     * Set the BCC recipients.
     *
     * @param  array<string|EmailAddress>  $recipients
     */
    public function bcc(array $recipients): self
    {
        $this->bcc = EmailAddressCollection::from($recipients);

        return $this;
    }

    /**
     * Set the reply-to address.
     */
    public function replyTo(string|EmailAddress $email, ?string $name = null): self
    {
        $this->replyTo = $email instanceof EmailAddress
            ? $email
            : new EmailAddress($email, $name);

        return $this;
    }

    /**
     * Set the subject.
     */
    public function subject(string $subject): self
    {
        $this->subject = new Subject($subject);

        return $this;
    }

    /**
     * Set the plain text content.
     */
    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Set the HTML content.
     */
    public function html(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Add an attachment.
     */
    public function attach(Attachment $attachment): self
    {
        if ($this->attachments === null) {
            $this->attachments = AttachmentCollection::from([$attachment]);
        } else {
            $this->attachments = $this->attachments->add($attachment);
        }

        return $this;
    }

    /**
     * Attach a file from a path.
     */
    public function attachFile(string $path, ?string $name = null, ?string $mimeType = null): self
    {
        return $this->attach(Attachment::fromFile($path, $name, $mimeType));
    }

    /**
     * Attach binary data.
     */
    public function attachData(string $data, string $name, string $mimeType): self
    {
        return $this->attach(Attachment::fromBinary($data, $name, $mimeType));
    }

    /**
     * Enable/disable click tracking.
     */
    public function withClickTracking(bool $enabled = true): self
    {
        $this->clickTracking = $enabled;

        return $this;
    }

    /**
     * Enable/disable open tracking.
     */
    public function withOpenTracking(bool $enabled = true): self
    {
        $this->openTracking = $enabled;

        return $this;
    }

    /**
     * Mark as transactional email.
     */
    public function transactional(bool $enabled = true): self
    {
        $this->transactional = $enabled;

        return $this;
    }

    /**
     * Enable/disable inline CSS processing.
     */
    public function withInlineCss(bool $enabled = true): self
    {
        $this->inlineCss = $enabled;

        return $this;
    }

    /**
     * Enable/disable template substitutions.
     */
    public function withSubstitutions(bool $enabled = true): self
    {
        $this->performSubstitutions = $enabled;

        return $this;
    }

    /**
     * Set metadata.
     *
     * @param  array<string, string>|Metadata  $metadata
     */
    public function metadata(array|Metadata $metadata): self
    {
        $this->metadata = $metadata instanceof Metadata
            ? $metadata
            : Metadata::from($metadata);

        return $this;
    }

    /**
     * Add a metadata value.
     */
    public function addMetadata(string $key, string $value): self
    {
        if ($this->metadata === null) {
            $this->metadata = Metadata::from([$key => $value]);
        } else {
            $this->metadata = $this->metadata->set($key, $value);
        }

        return $this;
    }

    /**
     * Set substitution data for templates.
     *
     * @param  array<string, mixed>|SubstitutionData  $data
     */
    public function substitutionData(array|SubstitutionData $data): self
    {
        $this->substitutionData = $data instanceof SubstitutionData
            ? $data
            : SubstitutionData::from($data);

        return $this;
    }

    /**
     * Add a substitution variable.
     */
    public function addSubstitution(string $key, mixed $value): self
    {
        if ($this->substitutionData === null) {
            $this->substitutionData = SubstitutionData::from([$key => $value]);
        } else {
            $this->substitutionData = $this->substitutionData->set($key, $value);
        }

        return $this;
    }

    /**
     * Set the tag.
     */
    public function tag(string|Tag $tag): self
    {
        $this->tag = $tag instanceof Tag ? $tag : new Tag($tag);

        return $this;
    }

    /**
     * Set the project ID.
     */
    public function projectId(int $projectId): self
    {
        $this->projectId = $projectId;

        return $this;
    }

    /**
     * Set the template slug.
     */
    public function templateSlug(string $templateSlug): self
    {
        $this->templateSlug = $templateSlug;

        return $this;
    }

    /**
     * Set the template version.
     */
    public function templateVersion(int $templateVersion): self
    {
        $this->templateVersion = $templateVersion;

        return $this;
    }

    /**
     * Use a template by slug and optionally version.
     */
    public function useTemplate(string $slug, ?int $version = null, ?int $projectId = null): self
    {
        $this->templateSlug = $slug;

        if ($version !== null) {
            $this->templateVersion = $version;
        }

        if ($projectId !== null) {
            $this->projectId = $projectId;
        }

        return $this;
    }

    /**
     * Build the SendEmailData object.
     *
     * @throws InvalidValueException
     */
    public function build(): SendEmailData
    {
        if ($this->from === null) {
            throw new InvalidValueException('From address is required.');
        }

        if ($this->to === null || $this->to->isEmpty()) {
            throw new InvalidValueException('At least one recipient is required.');
        }

        if ($this->subject === null && $this->templateSlug === null) {
            throw new InvalidValueException('Subject is required when not using a template.');
        }

        // Content is required unless using a template
        if ($this->text === null && $this->html === null && $this->templateSlug === null) {
            throw new InvalidValueException('Either text, html content, or a template is required.');
        }

        $options = new EmailOptions(
            clickTracking: $this->clickTracking,
            openTracking: $this->openTracking,
            transactional: $this->transactional,
            inlineCss: $this->inlineCss,
            performSubstitutions: $this->performSubstitutions,
        );

        return new SendEmailData(
            from: $this->from,
            to: $this->to,
            subject: $this->subject,
            text: $this->text,
            html: $this->html,
            cc: $this->cc,
            bcc: $this->bcc,
            replyTo: $this->replyTo,
            attachments: $this->attachments,
            options: $options,
            metadata: $this->metadata,
            substitutionData: $this->substitutionData,
            tag: $this->tag,
            projectId: $this->projectId,
            templateSlug: $this->templateSlug,
            templateVersion: $this->templateVersion,
        );
    }

    /**
     * Get the built SendEmailData object (alias for build()).
     *
     * @throws InvalidValueException
     */
    public function toData(): SendEmailData
    {
        return $this->build();
    }
}
