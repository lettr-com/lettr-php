<?php

declare(strict_types=1);

namespace Lettr\Responses;

use Lettr\Dto\Template\MergeTag;

/**
 * Response from the get template HTML endpoint.
 */
final readonly class GetTemplateHtmlResponse
{
    /**
     * @param  array<int, MergeTag>  $mergeTags
     */
    public function __construct(
        public string $html,
        public array $mergeTags,
        public ?string $subject = null,
    ) {}

    /**
     * Create from an API response array.
     *
     * @param  array{
     *     html: string,
     *     merge_tags: array<int, array{key: string, required: bool, type?: string|null, children?: array<int, array{key: string, type?: string|null}>|null}>,
     *     subject?: string|null,
     * }  $data
     */
    public static function from(array $data): self
    {
        return new self(
            html: $data['html'],
            mergeTags: array_map(
                static fn (array $tag): MergeTag => MergeTag::from($tag),
                $data['merge_tags'],
            ),
            subject: $data['subject'] ?? null,
        );
    }
}
