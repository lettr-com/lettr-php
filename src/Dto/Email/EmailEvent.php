<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

use Lettr\ValueObjects\Timestamp;

/**
 * Email event. Covers every event type via a flat shape: common properties
 * plus every union-specific field as nullable.
 */
final readonly class EmailEvent
{
    /**
     * @param  array<int, string>|null  $rcptTags
     * @param  array<string, mixed>|null  $rcptMeta
     */
    public function __construct(
        // Common — required by spec
        public string $eventId,
        public string $type,
        public Timestamp $timestamp,
        public string $requestId,
        public string $rcptTo,
        public ?string $rawRcptTo,
        public ?string $recipientDomain,
        public ?string $mailboxProvider,
        public ?string $mailboxProviderRegion,
        // Common — nullable
        public ?string $messageId = null,
        public ?string $subject = null,
        public ?string $friendlyFrom = null,
        public ?string $sendingDomain = null,
        public ?string $sendingIp = null,
        public ?bool $clickTracking = null,
        public ?bool $openTracking = null,
        public ?bool $transactional = null,
        public ?int $msgSize = null,
        public ?string $injectionTime = null,
        public ?array $rcptMeta = null,
        public ?string $campaignId = null,
        public ?string $templateId = null,
        public ?string $templateVersion = null,
        public ?string $ipPool = null,
        public ?string $msgFrom = null,
        public ?string $rcptType = null,
        public ?array $rcptTags = null,
        public ?bool $ampEnabled = null,
        public ?string $delvMethod = null,
        public ?string $recvMethod = null,
        public ?string $routingDomain = null,
        public ?string $scheduledTime = null,
        public ?string $abTestId = null,
        public ?string $abTestVersion = null,
        // Failure / delivery union
        public ?int $bounceClass = null,
        public ?string $errorCode = null,
        public ?string $reason = null,
        public ?string $rawReason = null,
        public ?int $numRetries = null,
        public ?int $queueTime = null,
        public ?string $outboundTls = null,
        public ?string $deviceToken = null,
        // Spam complaint
        public ?string $fbtype = null,
        public ?string $reportBy = null,
        public ?string $reportTo = null,
        // Policy rejection
        public ?string $remoteAddr = null,
        // Click / AMP click
        public ?string $targetLinkUrl = null,
        public ?string $targetLinkName = null,
        // Open / click engagement
        public ?string $userAgent = null,
        public ?UserAgentParsed $userAgentParsed = null,
        public ?GeoIp $geoIp = null,
        public ?string $ipAddress = null,
        public ?bool $initialPixel = null,
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        /** @var array<string, mixed>|null $userAgentParsed */
        $userAgentParsed = $data['user_agent_parsed'] ?? null;
        /** @var array<string, mixed>|null $geoIp */
        $geoIp = $data['geo_ip'] ?? null;

        return new self(
            eventId: (string) $data['event_id'],
            type: (string) $data['type'],
            timestamp: Timestamp::from($data['timestamp']),
            requestId: (string) $data['request_id'],
            rcptTo: (string) $data['rcpt_to'],
            rawRcptTo: isset($data['raw_rcpt_to']) ? (string) $data['raw_rcpt_to'] : null,
            recipientDomain: isset($data['recipient_domain']) ? (string) $data['recipient_domain'] : null,
            mailboxProvider: isset($data['mailbox_provider']) ? (string) $data['mailbox_provider'] : null,
            mailboxProviderRegion: isset($data['mailbox_provider_region']) ? (string) $data['mailbox_provider_region'] : null,
            messageId: $data['message_id'] ?? null,
            subject: $data['subject'] ?? null,
            friendlyFrom: $data['friendly_from'] ?? null,
            sendingDomain: $data['sending_domain'] ?? null,
            sendingIp: $data['sending_ip'] ?? null,
            clickTracking: $data['click_tracking'] ?? null,
            openTracking: $data['open_tracking'] ?? null,
            transactional: $data['transactional'] ?? null,
            msgSize: $data['msg_size'] ?? null,
            injectionTime: $data['injection_time'] ?? null,
            rcptMeta: $data['rcpt_meta'] ?? null,
            campaignId: $data['campaign_id'] ?? null,
            templateId: $data['template_id'] ?? null,
            templateVersion: isset($data['template_version']) ? (string) $data['template_version'] : null,
            ipPool: $data['ip_pool'] ?? null,
            msgFrom: $data['msg_from'] ?? null,
            rcptType: $data['rcpt_type'] ?? null,
            rcptTags: $data['rcpt_tags'] ?? null,
            ampEnabled: $data['amp_enabled'] ?? null,
            delvMethod: $data['delv_method'] ?? null,
            recvMethod: $data['recv_method'] ?? null,
            routingDomain: $data['routing_domain'] ?? null,
            scheduledTime: $data['scheduled_time'] ?? null,
            abTestId: $data['ab_test_id'] ?? null,
            abTestVersion: isset($data['ab_test_version']) ? (string) $data['ab_test_version'] : null,
            bounceClass: $data['bounce_class'] ?? null,
            errorCode: isset($data['error_code']) ? (string) $data['error_code'] : null,
            reason: $data['reason'] ?? null,
            rawReason: $data['raw_reason'] ?? null,
            numRetries: $data['num_retries'] ?? null,
            queueTime: $data['queue_time'] ?? null,
            outboundTls: isset($data['outbound_tls']) ? (string) $data['outbound_tls'] : null,
            deviceToken: $data['device_token'] ?? null,
            fbtype: $data['fbtype'] ?? null,
            reportBy: $data['report_by'] ?? null,
            reportTo: $data['report_to'] ?? null,
            remoteAddr: $data['remote_addr'] ?? null,
            targetLinkUrl: $data['target_link_url'] ?? null,
            targetLinkName: $data['target_link_name'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            userAgentParsed: is_array($userAgentParsed) ? UserAgentParsed::from($userAgentParsed) : null,
            geoIp: is_array($geoIp) ? GeoIp::from($geoIp) : null,
            ipAddress: $data['ip_address'] ?? null,
            initialPixel: $data['initial_pixel'] ?? null,
        );
    }
}
