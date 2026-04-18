<?php

declare(strict_types=1);

namespace Lettr\Dto\Domain;

use Lettr\Enums\DnsStatus;
use Lettr\ValueObjects\DomainName;

/**
 * Domain verification result.
 */
final readonly class DomainVerification
{
    public function __construct(
        public DomainName $domain,
        public DnsStatus $dkimStatus,
        public DnsStatus $cnameStatus,
        public DnsStatus $dmarcStatus,
        public DnsStatus $spfStatus,
        public bool $isPrimaryDomain,
        public ?string $ownershipVerified,
        public ?VerificationDns $dns,
        public ?DmarcVerification $dmarc,
        public ?SpfVerification $spf,
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array{
     *     domain: string,
     *     dkim_status: string,
     *     cname_status: string,
     *     dmarc_status: string,
     *     spf_status: string,
     *     is_primary_domain: bool,
     *     ownership_verified?: string|null,
     *     dns?: array{
     *         dkim_record?: string|null,
     *         cname_record?: string|null,
     *         dmarc_record?: string|null,
     *         spf_record?: string|null,
     *         dkim_error?: string|null,
     *         cname_error?: string|null,
     *         dmarc_error?: string|null,
     *         spf_error?: string|null,
     *     }|null,
     *     dmarc?: array{
     *         is_valid: bool,
     *         status: string,
     *         found_at_domain?: string|null,
     *         record?: string|null,
     *         policy?: string|null,
     *         subdomain_policy?: string|null,
     *         error?: string|null,
     *         covered_by_parent_policy: bool,
     *     }|null,
     *     spf?: array{
     *         is_valid: bool,
     *         status: string,
     *         record?: string|null,
     *         error?: string|null,
     *         includes_sparkpost: bool,
     *     }|null,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            domain: new DomainName($data['domain']),
            dkimStatus: DnsStatus::from($data['dkim_status']),
            cnameStatus: DnsStatus::from($data['cname_status']),
            dmarcStatus: DnsStatus::from($data['dmarc_status']),
            spfStatus: DnsStatus::from($data['spf_status']),
            isPrimaryDomain: $data['is_primary_domain'],
            ownershipVerified: $data['ownership_verified'] ?? null,
            dns: isset($data['dns']) ? VerificationDns::from($data['dns']) : null,
            dmarc: isset($data['dmarc']) ? DmarcVerification::from($data['dmarc']) : null,
            spf: isset($data['spf']) ? SpfVerification::from($data['spf']) : null,
        );
    }

    /**
     * Check if verification passed completely.
     */
    public function isFullyVerified(): bool
    {
        if ($this->dkimStatus !== DnsStatus::Valid) {
            return false;
        }

        if ($this->cnameStatus !== DnsStatus::Valid && $this->cnameStatus !== DnsStatus::NotApplicable) {
            return false;
        }

        return $this->dmarcStatus->isConfigured()
            && $this->spfStatus->isConfigured();
    }

    /**
     * Check if there are any verification errors.
     */
    public function hasErrors(): bool
    {
        return $this->dns !== null && (
            $this->dns->dkimError !== null
            || $this->dns->cnameError !== null
            || $this->dns->dmarcError !== null
            || $this->dns->spfError !== null
        );
    }

    /**
     * Get all error messages.
     *
     * @return array<string, string>
     */
    public function errors(): array
    {
        if ($this->dns === null) {
            return [];
        }

        $errors = [];

        if ($this->dns->dkimError !== null) {
            $errors['dkim'] = $this->dns->dkimError;
        }

        if ($this->dns->cnameError !== null) {
            $errors['cname'] = $this->dns->cnameError;
        }

        if ($this->dns->dmarcError !== null) {
            $errors['dmarc'] = $this->dns->dmarcError;
        }

        if ($this->dns->spfError !== null) {
            $errors['spf'] = $this->dns->spfError;
        }

        return $errors;
    }
}
