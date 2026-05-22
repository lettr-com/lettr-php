<?php

declare(strict_types=1);

namespace Lettr\Services\Audience;

use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Audience\AudienceProperty;
use Lettr\Dto\Audience\CreateAudiencePropertyData;
use Lettr\Dto\Audience\ListAudiencePropertiesFilter;
use Lettr\Dto\Audience\UpdateAudiencePropertyData;
use Lettr\Responses\ListAudiencePropertiesResponse;

/**
 * Service for managing audience properties via the Lettr API.
 */
final class AudiencePropertyService
{
    private const ENDPOINT = 'audience/properties';

    public function __construct(
        private readonly TransporterContract $transporter,
    ) {}

    public function list(?ListAudiencePropertiesFilter $filter = null): ListAudiencePropertiesResponse
    {
        $query = $filter?->toArray() ?? [];

        /**
         * @var array{
         *     properties: array<int, array{
         *         id: string,
         *         name: string,
         *         type: string,
         *         fallback_value: string|null,
         *         created_at: string,
         *     }>,
         *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
         * } $response
         */
        $response = $this->transporter->getWithQuery(self::ENDPOINT, $query);

        return ListAudiencePropertiesResponse::from($response);
    }

    public function get(string $propertyId): AudienceProperty
    {
        /**
         * @var array{
         *     id: string,
         *     name: string,
         *     type: string,
         *     fallback_value: string|null,
         *     created_at: string,
         * } $response
         */
        $response = $this->transporter->get(self::ENDPOINT.'/'.$propertyId);

        return AudienceProperty::from($response);
    }

    public function create(CreateAudiencePropertyData $data): AudienceProperty
    {
        /**
         * @var array{
         *     id: string,
         *     name: string,
         *     type: string,
         *     fallback_value: string|null,
         *     created_at: string,
         * } $response
         */
        $response = $this->transporter->post(self::ENDPOINT, $data->toArray());

        return AudienceProperty::from($response);
    }

    public function update(string $propertyId, UpdateAudiencePropertyData $data): AudienceProperty
    {
        /**
         * @var array{
         *     id: string,
         *     name: string,
         *     type: string,
         *     fallback_value: string|null,
         *     created_at: string,
         * } $response
         */
        $response = $this->transporter->patch(self::ENDPOINT.'/'.$propertyId, $data->toArray());

        return AudienceProperty::from($response);
    }

    public function delete(string $propertyId): void
    {
        $this->transporter->delete(self::ENDPOINT.'/'.$propertyId);
    }
}
