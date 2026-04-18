<?php

declare(strict_types=1);

namespace Lettr\Dto\Email;

/**
 * Parsed user-agent information from an open/click event.
 */
final readonly class UserAgentParsed
{
    public function __construct(
        public ?string $agentFamily,
        public ?string $deviceBrand,
        public ?string $deviceFamily,
        public ?string $osFamily,
        public ?string $osVersion,
        public ?bool $isMobile,
        public ?bool $isProxy,
        public ?bool $isPrefetched,
    ) {}

    /**
     * @param  array{
     *     agent_family?: string|null,
     *     device_brand?: string|null,
     *     device_family?: string|null,
     *     os_family?: string|null,
     *     os_version?: string|null,
     *     is_mobile?: bool|null,
     *     is_proxy?: bool|null,
     *     is_prefetched?: bool|null,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            agentFamily: $data['agent_family'] ?? null,
            deviceBrand: $data['device_brand'] ?? null,
            deviceFamily: $data['device_family'] ?? null,
            osFamily: $data['os_family'] ?? null,
            osVersion: $data['os_version'] ?? null,
            isMobile: $data['is_mobile'] ?? null,
            isProxy: $data['is_proxy'] ?? null,
            isPrefetched: $data['is_prefetched'] ?? null,
        );
    }
}
