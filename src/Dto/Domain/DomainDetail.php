<?php

declare(strict_types=1);

namespace Lettr\Dto\Domain;

use Lettr\Enums\DnsStatus;
use Lettr\Enums\DomainStatus;
use Lettr\ValueObjects\DomainName;
use Lettr\ValueObjects\Timestamp;

/**
 * Full domain details.
 */
final readonly class DomainDetail
{
    public function __construct(
        public DomainName $domain,
        public DomainStatus $status,
        public string $statusLabel,
        public bool $canSend,
        public ?DnsStatus $cnameStatus,
        public ?DnsStatus $dkimStatus,
        public ?DnsStatus $dmarcStatus,
        public ?DnsStatus $spfStatus,
        public bool $isPrimaryDomain,
        public ?string $trackingDomain,
        public ?DomainDkim $dkim,
        public ?DnsProvider $dnsProvider,
        public Timestamp $createdAt,
        public Timestamp $updatedAt,
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array{
     *     domain: string,
     *     status: string,
     *     status_label: string,
     *     can_send: bool,
     *     cname_status?: string|null,
     *     dkim_status?: string|null,
     *     dmarc_status?: string|null,
     *     spf_status?: string|null,
     *     is_primary_domain?: bool,
     *     tracking_domain?: string|null,
     *     dns?: array{dkim: array{selector: string, public: string, headers: string, signing_domain?: string}}|null,
     *     dns_provider?: array{provider: string, provider_label: string, nameservers: array<int, string>, error: string|null}|null,
     *     created_at: string,
     *     updated_at: string,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            domain: new DomainName($data['domain']),
            status: DomainStatus::from($data['status']),
            statusLabel: $data['status_label'],
            canSend: $data['can_send'],
            cnameStatus: isset($data['cname_status']) ? DnsStatus::from($data['cname_status']) : null,
            dkimStatus: isset($data['dkim_status']) ? DnsStatus::from($data['dkim_status']) : null,
            dmarcStatus: isset($data['dmarc_status']) ? DnsStatus::from($data['dmarc_status']) : null,
            spfStatus: isset($data['spf_status']) ? DnsStatus::from($data['spf_status']) : null,
            isPrimaryDomain: $data['is_primary_domain'] ?? false,
            trackingDomain: $data['tracking_domain'] ?? null,
            dkim: isset($data['dns']['dkim']) ? DomainDkim::from($data['dns']['dkim']) : null,
            dnsProvider: isset($data['dns_provider']) ? DnsProvider::from($data['dns_provider']) : null,
            createdAt: Timestamp::fromString($data['created_at']),
            updatedAt: Timestamp::fromString($data['updated_at']),
        );
    }

    /**
     * Check if the domain is fully verified.
     */
    public function isVerified(): bool
    {
        return $this->status === DomainStatus::Approved
            && $this->dkimStatus === DnsStatus::Valid
            && ($this->cnameStatus === DnsStatus::Valid || $this->cnameStatus === DnsStatus::NotApplicable);
    }
}
