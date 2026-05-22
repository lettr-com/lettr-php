<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;

/**
 * Request body for creating an audience list.
 */
final readonly class CreateAudienceListData implements Arrayable
{
    public function __construct(
        public string $name,
    ) {}

    /**
     * @return array{name: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
