<?php

declare(strict_types=1);

namespace Lettr\Dto\Template;

use Lettr\Enums\TemplatePreparationStatus;
use Lettr\Enums\TemplatePurpose;
use Lettr\ValueObjects\Timestamp;

/**
 * Response from creating a template.
 */
final readonly class CreatedTemplate
{
    /**
     * @param  array<int, MergeTag>  $mergeTags
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public int $projectId,
        public int $folderId,
        public int $activeVersion,
        public array $mergeTags,
        public Timestamp $createdAt,
        public TemplatePurpose $purpose = TemplatePurpose::Transactional,
        public TemplatePreparationStatus $preparationStatus = TemplatePreparationStatus::Ready,
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     project_id: int,
     *     folder_id: int,
     *     purpose?: string|null,
     *     preparation_status?: string|null,
     *     active_version: int,
     *     merge_tags: array<int, array{key: string, required: bool, children?: array<int, array{key: string, type?: string|null}>}>,
     *     created_at: string,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            slug: $data['slug'],
            projectId: $data['project_id'],
            folderId: $data['folder_id'],
            activeVersion: $data['active_version'],
            mergeTags: array_map(
                static fn (array $tag): MergeTag => MergeTag::from($tag),
                $data['merge_tags'],
            ),
            createdAt: Timestamp::fromString($data['created_at']),
            purpose: TemplatePurpose::tryFrom((string) ($data['purpose'] ?? '')) ?? TemplatePurpose::Transactional,
            preparationStatus: TemplatePreparationStatus::fromResponse($data['preparation_status'] ?? null),
        );
    }
}
