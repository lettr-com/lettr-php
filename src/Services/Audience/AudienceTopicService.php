<?php

declare(strict_types=1);

namespace Lettr\Services\Audience;

use Lettr\Contracts\TransporterContract;
use Lettr\Dto\Audience\AudienceTopic;
use Lettr\Dto\Audience\CreateAudienceTopicData;
use Lettr\Dto\Audience\ListAudienceTopicsFilter;
use Lettr\Dto\Audience\UpdateAudienceTopicData;
use Lettr\Responses\ListAudienceTopicsResponse;

/**
 * Service for managing audience topics via the Lettr API.
 */
final class AudienceTopicService
{
    private const ENDPOINT = 'audience/topics';

    public function __construct(
        private readonly TransporterContract $transporter,
    ) {}

    public function list(?ListAudienceTopicsFilter $filter = null): ListAudienceTopicsResponse
    {
        $query = $filter?->toArray() ?? [];

        /**
         * @var array{
         *     topics: array<int, array{
         *         id: string,
         *         name: string,
         *         description: string|null,
         *         default_subscription: string,
         *         visibility: string,
         *         contacts_count: int,
         *         created_at: string|null,
         *     }>,
         *     pagination: array{current_page: int, last_page: int, per_page: int, total: int},
         * } $response
         */
        $response = $this->transporter->getWithQuery(self::ENDPOINT, $query);

        return ListAudienceTopicsResponse::from($response);
    }

    public function get(string $topicId): AudienceTopic
    {
        /**
         * @var array{
         *     id: string,
         *     name: string,
         *     description: string|null,
         *     default_subscription: string,
         *     visibility: string,
         *     contacts_count: int,
         *     created_at: string|null,
         * } $response
         */
        $response = $this->transporter->get(self::ENDPOINT.'/'.$topicId);

        return AudienceTopic::from($response);
    }

    public function create(CreateAudienceTopicData $data): AudienceTopic
    {
        /**
         * @var array{
         *     id: string,
         *     name: string,
         *     description: string|null,
         *     default_subscription: string,
         *     visibility: string,
         *     contacts_count: int,
         *     created_at: string|null,
         * } $response
         */
        $response = $this->transporter->post(self::ENDPOINT, $data->toArray());

        return AudienceTopic::from($response);
    }

    public function update(string $topicId, UpdateAudienceTopicData $data): AudienceTopic
    {
        /**
         * @var array{
         *     id: string,
         *     name: string,
         *     description: string|null,
         *     default_subscription: string,
         *     visibility: string,
         *     contacts_count: int,
         *     created_at: string|null,
         * } $response
         */
        $response = $this->transporter->patch(self::ENDPOINT.'/'.$topicId, $data->toArray());

        return AudienceTopic::from($response);
    }

    public function delete(string $topicId): void
    {
        $this->transporter->delete(self::ENDPOINT.'/'.$topicId);
    }
}
