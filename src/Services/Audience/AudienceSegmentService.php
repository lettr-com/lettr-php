<?php

declare(strict_types=1);

namespace Lettr\Services\Audience;

use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Audience\AudienceSegment;
use Lettr\Dto\Audience\CreateAudienceSegmentData;
use Lettr\Dto\Audience\ListAudienceSegmentsFilter;
use Lettr\Dto\Audience\UpdateAudienceSegmentData;
use Lettr\Responses\ListAudienceSegmentsResponse;

/**
 * Service for managing audience segments via the Lettr API.
 */
final class AudienceSegmentService
{
    private const ENDPOINT = 'audience/segments';

    public function __construct(
        private readonly TransporterContract $transporter,
    ) {}

    public function list(?ListAudienceSegmentsFilter $filter = null): ListAudienceSegmentsResponse
    {
        $query = $filter?->toArray() ?? [];

        /**
         * @var array{
         *     segments: array<int, array{
         *         id: string,
         *         name: string,
         *         list_id: string|null,
         *         list_name: string|null,
         *         condition_groups: array<int, array{conditions: array<int, array{field: string, operator: string, value?: string|null}>}>,
         *         cached_contacts_count: int|null,
         *         created_at: string,
         *     }>,
         *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
         * } $response
         */
        $response = $this->transporter->getWithQuery(self::ENDPOINT, $query);

        return ListAudienceSegmentsResponse::from($response);
    }

    public function get(string $segmentId): AudienceSegment
    {
        /**
         * @var array{
         *     id: string,
         *     name: string,
         *     list_id: string|null,
         *     list_name: string|null,
         *     condition_groups: array<int, array{conditions: array<int, array{field: string, operator: string, value?: string|null}>}>,
         *     cached_contacts_count: int|null,
         *     created_at: string,
         * } $response
         */
        $response = $this->transporter->get(self::ENDPOINT.'/'.$segmentId);

        return AudienceSegment::from($response);
    }

    public function create(CreateAudienceSegmentData $data): AudienceSegment
    {
        /**
         * @var array{
         *     id: string,
         *     name: string,
         *     list_id: string|null,
         *     list_name: string|null,
         *     condition_groups: array<int, array{conditions: array<int, array{field: string, operator: string, value?: string|null}>}>,
         *     cached_contacts_count: int|null,
         *     created_at: string,
         * } $response
         */
        $response = $this->transporter->post(self::ENDPOINT, $data->toArray());

        return AudienceSegment::from($response);
    }

    public function update(string $segmentId, UpdateAudienceSegmentData $data): AudienceSegment
    {
        /**
         * @var array{
         *     id: string,
         *     name: string,
         *     list_id: string|null,
         *     list_name: string|null,
         *     condition_groups: array<int, array{conditions: array<int, array{field: string, operator: string, value?: string|null}>}>,
         *     cached_contacts_count: int|null,
         *     created_at: string,
         * } $response
         */
        $response = $this->transporter->patch(self::ENDPOINT.'/'.$segmentId, $data->toArray());

        return AudienceSegment::from($response);
    }

    public function delete(string $segmentId): void
    {
        $this->transporter->delete(self::ENDPOINT.'/'.$segmentId);
    }
}
