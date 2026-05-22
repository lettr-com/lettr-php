<?php

declare(strict_types=1);

namespace Lettr\Dto\Audience;

use Lettr\Contracts\Arrayable;
use Lettr\Enums\SegmentOperator;

/**
 * A single segment condition (field + operator + optional value).
 *
 * Used both in segment responses (under `condition_groups`) and in segment
 * write requests (under `conditions.groups[].conditions`).
 */
final readonly class SegmentCondition implements Arrayable
{
    public function __construct(
        public string $field,
        public SegmentOperator $operator,
        public ?string $value = null,
    ) {}

    /**
     * @param  array{field: string, operator: string, value?: string|null}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            field: $data['field'],
            operator: SegmentOperator::from($data['operator']),
            value: $data['value'] ?? null,
        );
    }

    /**
     * @return array{field: string, operator: string, value?: string|null}
     */
    public function toArray(): array
    {
        $payload = [
            'field' => $this->field,
            'operator' => $this->operator->value,
        ];

        if ($this->operator->requiresValue() || $this->value !== null) {
            $payload['value'] = $this->value;
        }

        return $payload;
    }
}
