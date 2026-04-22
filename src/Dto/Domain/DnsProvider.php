<?php

declare(strict_types=1);

namespace Lettr\Dto\Domain;

/**
 * Detected DNS provider for a sending domain.
 */
final readonly class DnsProvider
{
    /**
     * @param  array<int, string>  $nameservers
     */
    public function __construct(
        public string $provider,
        public string $providerLabel,
        public array $nameservers,
        public ?string $error,
    ) {}

    /**
     * @param  array{
     *     provider: string,
     *     provider_label: string,
     *     nameservers: array<int, string>,
     *     error: string|null,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            provider: $data['provider'],
            providerLabel: $data['provider_label'],
            nameservers: $data['nameservers'],
            error: $data['error'],
        );
    }
}
