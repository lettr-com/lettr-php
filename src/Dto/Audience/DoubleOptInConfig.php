<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Double opt-in configuration for contact creation.
 *
 * When supplied, the contact is created in `unverified` status and receives
 * a confirmation email; they become `subscribed` only after clicking the
 * confirmation link.
 */
final readonly class DoubleOptInConfig implements Arrayable
{
    public function __construct(
        public string $from,
        public string $subject,
        public string $templateSlug,
        public string $redirectUrl,
        public ?string $fromName = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'from' => $this->from,
            'subject' => $this->subject,
            'template_slug' => $this->templateSlug,
            'redirect_url' => $this->redirectUrl,
        ];

        if ($this->fromName !== null) {
            $payload['from_name'] = $this->fromName;
        }

        return $payload;
    }
}
