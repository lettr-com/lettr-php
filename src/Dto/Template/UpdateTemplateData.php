<?php

declare(strict_types=1);

namespace Lettr\Dto\Template;

use Lettr\Contracts\Arrayable;

/**
 * Data Transfer Object for updating a template.
 */
final readonly class UpdateTemplateData implements Arrayable
{
    public function __construct(
        public ?int $projectId = null,
        public ?string $name = null,
        public ?string $html = null,
        public ?string $json = null,
    ) {}

    /**
     * Convert the DTO to an array for API request.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->projectId !== null) {
            $data['project_id'] = $this->projectId;
        }

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->html !== null) {
            $data['html'] = $this->html;
        }

        if ($this->json !== null) {
            $data['json'] = $this->json;
        }

        return $data;
    }
}
