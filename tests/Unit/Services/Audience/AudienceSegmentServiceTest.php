<?php

declare(strict_types=1);

use Lettr\Dto\Audience\CreateAudienceSegmentData;
use Lettr\Dto\Audience\ListAudienceSegmentsFilter;
use Lettr\Dto\Audience\SegmentCondition;
use Lettr\Dto\Audience\SegmentConditionGroup;
use Lettr\Dto\Audience\SegmentConditionsInput;
use Lettr\Dto\Audience\UpdateAudienceSegmentData;
use Lettr\Enums\SegmentOperator;
use Lettr\Services\Audience\AudienceSegmentService;
use Tests\Support\MockTransporter;

function sampleSegmentResponse(): array
{
    return [
        'id' => 's-1',
        'name' => 'Pro users',
        'list_id' => 'l-1',
        'list_name' => 'Newsletter',
        'condition_groups' => [
            ['conditions' => [
                ['field' => 'plan', 'operator' => 'equals', 'value' => 'pro'],
            ]],
        ],
        'cached_contacts_count' => 42,
        'created_at' => '2026-01-01T00:00:00+00:00',
    ];
}

test('list forwards list_id query', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'segments' => [sampleSegmentResponse()],
        'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
    ];

    $service = new AudienceSegmentService($transporter);
    $service->list(ListAudienceSegmentsFilter::create()->listId('l-1'));

    expect($transporter->lastUri)->toBe('audience/segments')
        ->and($transporter->lastQuery)->toBe(['list_id' => 'l-1']);
});

test('get parses condition_groups into typed structure', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sampleSegmentResponse();

    $service = new AudienceSegmentService($transporter);
    $segment = $service->get('s-1');

    expect($transporter->lastUri)->toBe('audience/segments/s-1')
        ->and($segment->conditionGroups)->toHaveCount(1)
        ->and($segment->conditionGroups[0]->conditions)->toHaveCount(1)
        ->and($segment->conditionGroups[0]->conditions[0]->field)->toBe('plan')
        ->and($segment->conditionGroups[0]->conditions[0]->operator)->toBe(SegmentOperator::Equals)
        ->and($segment->conditionGroups[0]->conditions[0]->value)->toBe('pro');
});

test('create POSTs audience/segments with conditions wrapped in groups', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sampleSegmentResponse();

    $service = new AudienceSegmentService($transporter);
    $service->create(new CreateAudienceSegmentData(
        name: 'Pro users',
        conditions: new SegmentConditionsInput([
            new SegmentConditionGroup([
                new SegmentCondition('plan', SegmentOperator::Equals, 'pro'),
            ]),
        ]),
        listId: 'l-1',
    ));

    expect($transporter->lastUri)->toBe('audience/segments')
        ->and($transporter->lastData)->toBe([
            'name' => 'Pro users',
            'conditions' => [
                'groups' => [
                    ['conditions' => [
                        ['field' => 'plan', 'operator' => 'equals', 'value' => 'pro'],
                    ]],
                ],
            ],
            'list_id' => 'l-1',
        ]);
});

test('SegmentCondition with is_true operator omits value', function (): void {
    $payload = (new SegmentCondition('verified', SegmentOperator::IsTrue))->toArray();

    expect($payload)->toBe(['field' => 'verified', 'operator' => 'is_true']);
});

test('update can clear listId via withClearedListId', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = sampleSegmentResponse();

    $service = new AudienceSegmentService($transporter);
    $service->update('s-1', UpdateAudienceSegmentData::empty()->withName('All lists')->withClearedListId());

    expect($transporter->lastUri)->toBe('audience/segments/s-1')
        ->and($transporter->lastData)->toBe([
            'name' => 'All lists',
            'list_id' => null,
        ]);
});

test('delete hits DELETE audience/segments/{id}', function (): void {
    $transporter = new MockTransporter;

    $service = new AudienceSegmentService($transporter);
    $service->delete('s-1');

    expect($transporter->lastUri)->toBe('audience/segments/s-1');
});
