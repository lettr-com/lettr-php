<?php

declare(strict_types=1);

namespace Lettr\Services;

use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Template\CreatedTemplate;
use Lettr\Dto\Template\CreateTemplateData;
use Lettr\Dto\Template\ListTemplatesFilter;
use Lettr\Dto\Template\TemplateDetail;
use Lettr\Dto\Template\UpdatedTemplate;
use Lettr\Dto\Template\UpdateTemplateData;
use Lettr\Responses\GetMergeTagsResponse;
use Lettr\Responses\GetTemplateHtmlResponse;
use Lettr\Responses\ListTemplatesResponse;

/**
 * Service for managing templates via the Lettr API.
 */
final class TemplateService
{
    private const TEMPLATES_ENDPOINT = 'templates';

    public function __construct(
        private readonly TransporterContract $transporter,
    ) {}

    /**
     * List templates with optional filtering.
     */
    public function list(?ListTemplatesFilter $filter = null): ListTemplatesResponse
    {
        $query = $filter?->toArray() ?? [];

        /**
         * @var array{
         *     templates: array<int, array{
         *         id: int,
         *         name: string,
         *         slug: string,
         *         project_id: int,
         *         folder_id?: int|null,
         *         created_at: string,
         *         updated_at: string,
         *     }>,
         *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
         * } $response
         */
        $response = $this->transporter->getWithQuery(self::TEMPLATES_ENDPOINT, $query);

        return ListTemplatesResponse::from($response);
    }

    /**
     * Get template details by slug.
     */
    public function get(string $slug, ?int $projectId = null): TemplateDetail
    {
        $query = [];
        if ($projectId !== null) {
            $query['project_id'] = $projectId;
        }

        /**
         * @var array{
         *     id: int,
         *     name: string,
         *     slug: string,
         *     project_id: int,
         *     folder_id?: int|null,
         *     active_version: int|null,
         *     versions_count: int,
         *     html: string|null,
         *     json?: string|null,
         *     created_at: string,
         *     updated_at: string,
         * } $response
         */
        $response = $this->transporter->getWithQuery(self::TEMPLATES_ENDPOINT.'/'.$slug, $query);

        return TemplateDetail::from($response);
    }

    /**
     * Create a new template.
     */
    public function create(CreateTemplateData $data): CreatedTemplate
    {
        /**
         * @var array{
         *     id: int,
         *     name: string,
         *     slug: string,
         *     project_id: int,
         *     folder_id: int,
         *     active_version: int,
         *     merge_tags: array<int, array{key: string, required: bool, children?: array<int, array{key: string, type?: string|null}>}>,
         *     created_at: string,
         * } $response
         */
        $response = $this->transporter->post(self::TEMPLATES_ENDPOINT, $data->toArray());

        return CreatedTemplate::from($response);
    }

    /**
     * Delete a template by slug.
     */
    public function delete(string $slug, ?int $projectId = null): void
    {
        $endpoint = self::TEMPLATES_ENDPOINT.'/'.$slug;

        if ($projectId !== null) {
            $endpoint .= '?project_id='.$projectId;
        }

        $this->transporter->delete($endpoint);
    }

    /**
     * Update a template by slug.
     */
    public function update(string $slug, UpdateTemplateData $data): UpdatedTemplate
    {
        /**
         * @var array{
         *     id: int,
         *     name: string,
         *     slug: string,
         *     project_id: int,
         *     folder_id: int,
         *     active_version: int,
         *     merge_tags: array<int, array{key: string, required: bool, children?: array<int, array{key: string, type?: string|null}>}>,
         *     created_at: string,
         *     updated_at: string,
         * } $response
         */
        $response = $this->transporter->put(self::TEMPLATES_ENDPOINT.'/'.$slug, $data->toArray());

        return UpdatedTemplate::from($response);
    }

    /**
     * Get the rendered HTML for a template.
     */
    public function getHtml(int $projectId, string $slug): GetTemplateHtmlResponse
    {
        /**
         * @var array{
         *     html: string,
         *     merge_tags: array<int, array{key: string, required: bool, type?: string|null, children?: array<int, array{key: string, type?: string|null}>|null}>,
         *     subject?: string|null,
         * } $response
         */
        $response = $this->transporter->getWithQuery(self::TEMPLATES_ENDPOINT.'/html', [
            'project_id' => $projectId,
            'slug' => $slug,
        ]);

        return GetTemplateHtmlResponse::from($response);
    }

    /**
     * Get merge tags for a template.
     */
    public function getMergeTags(string $slug, ?int $projectId = null, ?int $version = null): GetMergeTagsResponse
    {
        $query = [];
        if ($projectId !== null) {
            $query['project_id'] = $projectId;
        }
        if ($version !== null) {
            $query['version'] = $version;
        }

        /**
         * @var array{
         *     project_id: int,
         *     template_slug: string,
         *     version: int,
         *     merge_tags: array<int, array{key: string, required: bool, type?: string|null, children?: array<int, array{key: string, type?: string|null}>|null}>,
         * } $response
         */
        $response = $this->transporter->getWithQuery(self::TEMPLATES_ENDPOINT.'/'.$slug.'/merge-tags', $query);

        return GetMergeTagsResponse::from($response);
    }
}
