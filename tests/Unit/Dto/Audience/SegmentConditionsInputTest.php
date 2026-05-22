<?php

declare(strict_types=1);

use Lettr\Dto\Audience\SegmentCondition;
use Lettr\Dto\Audience\SegmentConditionGroup;
use Lettr\Dto\Audience\SegmentConditionsInput;
use Lettr\Enums\SegmentOperator;

test('toArray wraps groups under the groups key with snake_case operators', function (): void {
    $input = new SegmentConditionsInput([
        new SegmentConditionGroup([
            new SegmentCondition('plan', SegmentOperator::Equals, 'pro'),
            new SegmentCondition('signed_up_at', SegmentOperator::After, '2026-01-01'),
        ]),
        new SegmentConditionGroup([
            new SegmentCondition('verified', SegmentOperator::IsTrue),
        ]),
    ]);

    expect($input->toArray())->toBe([
        'groups' => [
            ['conditions' => [
                ['field' => 'plan', 'operator' => 'equals', 'value' => 'pro'],
                ['field' => 'signed_up_at', 'operator' => 'after', 'value' => '2026-01-01'],
            ]],
            ['conditions' => [
                ['field' => 'verified', 'operator' => 'is_true'],
            ]],
        ],
    ]);
});

test('SegmentConditionGroup round-trips from response shape', function (): void {
    $raw = [
        'conditions' => [
            ['field' => 'plan', 'operator' => 'equals', 'value' => 'pro'],
        ],
    ];

    $group = SegmentConditionGroup::from($raw);

    expect($group->conditions[0]->field)->toBe('plan')
        ->and($group->conditions[0]->operator)->toBe(SegmentOperator::Equals)
        ->and($group->toArray())->toBe($raw);
});
