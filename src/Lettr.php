<?php

declare(strict_types=1);

namespace Lettr;

use Lettr\Contracts\TransporterContract;
use Lettr\Dto\RateLimit;
use Lettr\Services\AudienceService;
use Lettr\Services\DomainService;
use Lettr\Services\EmailService;
use Lettr\Services\HealthService;
use Lettr\Services\ProjectService;
use Lettr\Services\TemplateService;
use Lettr\Services\WebhookService;

/**
 * Lettr SDK entry point.
 *
 * @property-read EmailService $emails
 * @property-read DomainService $domains
 * @property-read WebhookService $webhooks
 * @property-read TemplateService $templates
 * @property-read ProjectService $projects
 * @property-read AudienceService $audience
 * @property-read HealthService $health
 */
final class Lettr
{
    /**
     * The current SDK version.
     */
    public const VERSION = '2.0.0';

    /**
     * The API base URL.
     */
    public const BASE_URL = 'https://app.lettr.com/api/';

    private ?EmailService $emailService = null;

    private ?DomainService $domainService = null;

    private ?WebhookService $webhookService = null;

    private ?TemplateService $templateService = null;

    private ?ProjectService $projectService = null;

    private ?AudienceService $audienceService = null;

    private ?HealthService $healthService = null;

    public function __construct(
        private readonly TransporterContract $client,
    ) {}

    /**
     * Create a new Lettr instance with the given API key.
     */
    public static function client(string $apiKey): self
    {
        return new self(new Client($apiKey));
    }

    /**
     * Get the email service.
     */
    public function emails(): EmailService
    {
        if ($this->emailService === null) {
            $this->emailService = new EmailService($this->client);
        }

        return $this->emailService;
    }

    /**
     * Get the domain service.
     */
    public function domains(): DomainService
    {
        if ($this->domainService === null) {
            $this->domainService = new DomainService($this->client);
        }

        return $this->domainService;
    }

    /**
     * Get the webhook service.
     */
    public function webhooks(): WebhookService
    {
        if ($this->webhookService === null) {
            $this->webhookService = new WebhookService($this->client);
        }

        return $this->webhookService;
    }

    /**
     * Get the template service.
     */
    public function templates(): TemplateService
    {
        if ($this->templateService === null) {
            $this->templateService = new TemplateService($this->client);
        }

        return $this->templateService;
    }

    /**
     * Get the project service.
     */
    public function projects(): ProjectService
    {
        if ($this->projectService === null) {
            $this->projectService = new ProjectService($this->client);
        }

        return $this->projectService;
    }

    /**
     * Get the audience service (lists, contacts, topics, properties, segments).
     */
    public function audience(): AudienceService
    {
        if ($this->audienceService === null) {
            $this->audienceService = new AudienceService($this->client);
        }

        return $this->audienceService;
    }

    /**
     * Get the health service.
     */
    public function health(): HealthService
    {
        if ($this->healthService === null) {
            $this->healthService = new HealthService($this->client);
        }

        return $this->healthService;
    }

    /**
     * Get the rate limit from the last API response.
     *
     * Available on every API response (3 requests per second per team).
     */
    public function lastRateLimit(): ?RateLimit
    {
        return RateLimit::fromHeaders($this->client->lastResponseHeaders());
    }

    /**
     * Get the response headers from the last API request.
     *
     * @return array<string, string|string[]>
     */
    public function lastResponseHeaders(): array
    {
        return $this->client->lastResponseHeaders();
    }

    /**
     * Magic method to access services as properties.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'emails' => $this->emails(),
            'domains' => $this->domains(),
            'webhooks' => $this->webhooks(),
            'templates' => $this->templates(),
            'projects' => $this->projects(),
            'audience' => $this->audience(),
            'health' => $this->health(),
            default => throw new \InvalidArgumentException("Unknown service: {$name}"),
        };
    }
}
