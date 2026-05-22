<?php

declare(strict_types=1);

namespace Lettr\Services;

use InvalidArgumentException;
use Lettr\Contracts\TransporterContract;
use Lettr\Services\Audience\AudienceContactService;
use Lettr\Services\Audience\AudienceListService;
use Lettr\Services\Audience\AudiencePropertyService;
use Lettr\Services\Audience\AudienceSegmentService;
use Lettr\Services\Audience\AudienceTopicService;

/**
 * Umbrella service exposing the five audience sub-services.
 *
 * @property-read AudienceListService $lists
 * @property-read AudienceContactService $contacts
 * @property-read AudienceTopicService $topics
 * @property-read AudiencePropertyService $properties
 * @property-read AudienceSegmentService $segments
 */
final class AudienceService
{
    private ?AudienceListService $listService = null;

    private ?AudienceContactService $contactService = null;

    private ?AudienceTopicService $topicService = null;

    private ?AudiencePropertyService $propertyService = null;

    private ?AudienceSegmentService $segmentService = null;

    public function __construct(
        private readonly TransporterContract $transporter,
    ) {}

    public function lists(): AudienceListService
    {
        if ($this->listService === null) {
            $this->listService = new AudienceListService($this->transporter);
        }

        return $this->listService;
    }

    public function contacts(): AudienceContactService
    {
        if ($this->contactService === null) {
            $this->contactService = new AudienceContactService($this->transporter);
        }

        return $this->contactService;
    }

    public function topics(): AudienceTopicService
    {
        if ($this->topicService === null) {
            $this->topicService = new AudienceTopicService($this->transporter);
        }

        return $this->topicService;
    }

    public function properties(): AudiencePropertyService
    {
        if ($this->propertyService === null) {
            $this->propertyService = new AudiencePropertyService($this->transporter);
        }

        return $this->propertyService;
    }

    public function segments(): AudienceSegmentService
    {
        if ($this->segmentService === null) {
            $this->segmentService = new AudienceSegmentService($this->transporter);
        }

        return $this->segmentService;
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'lists' => $this->lists(),
            'contacts' => $this->contacts(),
            'topics' => $this->topics(),
            'properties' => $this->properties(),
            'segments' => $this->segments(),
            default => throw new InvalidArgumentException("Unknown audience sub-service: {$name}"),
        };
    }
}
