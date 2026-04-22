<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

use Lettr\ValueObjects\Timestamp;

/**
 * Data Transfer Object representing a sent email list item.
 */
final readonly class SentEmail
{
    /**
     * @param  array<string, mixed>|null  $rcptMeta
     */
    public function __construct(
        public string $eventId,
        public string $type,
        public Timestamp $timestamp,
        public ?string $requestId = null,
        public ?string $messageId = null,
        public ?string $subject = null,
        public ?string $friendlyFrom = null,
        public ?string $sendingDomain = null,
        public ?string $rcptTo = null,
        public ?string $rawRcptTo = null,
        public ?string $recipientDomain = null,
        public ?string $mailboxProvider = null,
        public ?string $mailboxProviderRegion = null,
        public ?string $sendingIp = null,
        public ?bool $clickTracking = null,
        public ?bool $openTracking = null,
        public ?bool $transactional = null,
        public ?int $msgSize = null,
        public ?string $injectionTime = null,
        public ?array $rcptMeta = null,
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        return new self(
            eventId: $data['event_id'],
            type: $data['type'],
            timestamp: Timestamp::from($data['timestamp']),
            requestId: $data['request_id'] ?? null,
            messageId: $data['message_id'] ?? null,
            subject: $data['subject'] ?? null,
            friendlyFrom: $data['friendly_from'] ?? null,
            sendingDomain: $data['sending_domain'] ?? null,
            rcptTo: $data['rcpt_to'] ?? null,
            rawRcptTo: $data['raw_rcpt_to'] ?? null,
            recipientDomain: $data['recipient_domain'] ?? null,
            mailboxProvider: $data['mailbox_provider'] ?? null,
            mailboxProviderRegion: $data['mailbox_provider_region'] ?? null,
            sendingIp: $data['sending_ip'] ?? null,
            clickTracking: $data['click_tracking'] ?? null,
            openTracking: $data['open_tracking'] ?? null,
            transactional: $data['transactional'] ?? null,
            msgSize: $data['msg_size'] ?? null,
            injectionTime: $data['injection_time'] ?? null,
            rcptMeta: $data['rcpt_meta'] ?? null,
        );
    }
}
