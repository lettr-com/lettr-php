<?php

declare(strict_types=1);

namespace Lettr\Dto\Domain;

use Lettr\Enums\DnsStatus;
use Lettr\Enums\DomainStatus;
use Lettr\ValueObjects\DomainName;
use Lettr\ValueObjects\Timestamp;

/**
 * Domain list item.
 */
final readonly class Domain
{
    public function __construct(
        public DomainName $domain,
        public DomainStatus $status,
        public string $statusLabel,
        public bool $canSend,
        public ?DnsStatus $cnameStatus,
        public ?DnsStatus $dkimStatus,
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
            createdAt: Timestamp::fromString($data['created_at']),
            updatedAt: Timestamp::fromString($data['updated_at']),
        );
    }

    /**
     * Check if the domain is fully verified for sending.
     */
    public function isVerified(): bool
    {
        return $this->status === DomainStatus::Approved
            && $this->dkimStatus === DnsStatus::Valid
            && ($this->cnameStatus === DnsStatus::Valid || $this->cnameStatus === DnsStatus::NotApplicable);
    }

    /**
     * Check if DNS configuration needs attention.
     */
    public function needsDnsConfiguration(): bool
    {
        if ($this->dkimStatus !== DnsStatus::Valid) {
            return true;
        }

        return $this->cnameStatus !== DnsStatus::Valid
            && $this->cnameStatus !== DnsStatus::NotApplicable;
    }
}
