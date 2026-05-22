<?php

declare(strict_types=1);

use Lettr\Dto\Audience\CreateAudiencePropertyData;
use Lettr\Dto\Audience\UpdateAudiencePropertyData;
use Lettr\Enums\AudiencePropertyType;
use Lettr\Services\Audience\AudiencePropertyService;
use Tests\Support\MockTransporter;

function samplePropertyResponse(): array
{
    return [
        'id' => 'p-1',
        'name' => 'plan',
        'type' => 'string',
        'fallback_value' => 'free',
        'created_at' => '2026-01-01T00:00:00+00:00',
    ];
}

test('list GETs audience/properties', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'properties' => [samplePropertyResponse()],
        'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
    ];

    $service = new AudiencePropertyService($transporter);
    $response = $service->list();

    expect($transporter->lastUri)->toBe('audience/properties')
        ->and($response->properties->all()[0]->type)->toBe(AudiencePropertyType::StringType);
});

test('get GETs audience/properties/{id}', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = samplePropertyResponse();

    $service = new AudiencePropertyService($transporter);
    $service->get('p-1');

    expect($transporter->lastUri)->toBe('audience/properties/p-1');
});

test('create POSTs audience/properties with type enum serialized', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = samplePropertyResponse();

    $service = new AudiencePropertyService($transporter);
    $service->create(new CreateAudiencePropertyData(
        name: 'plan',
        type: AudiencePropertyType::StringType,
        fallbackValue: 'free',
    ));

    expect($transporter->lastUri)->toBe('audience/properties')
        ->and($transporter->lastData)->toBe([
            'name' => 'plan',
            'type' => 'string',
            'fallback_value' => 'free',
        ]);
});

test('update PATCHes audience/properties/{id} and never sends type', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = samplePropertyResponse();

    $service = new AudiencePropertyService($transporter);
    $service->update('p-1', UpdateAudiencePropertyData::withFallback('starter'));

    expect($transporter->lastUri)->toBe('audience/properties/p-1')
        ->and($transporter->lastData)->toBe(['fallback_value' => 'starter'])
        ->and($transporter->lastData)->not->toHaveKey('type')
        ->and($transporter->lastData)->not->toHaveKey('name');
});

test('update with clearFallback sends explicit null', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = samplePropertyResponse();

    $service = new AudiencePropertyService($transporter);
    $service->update('p-1', UpdateAudiencePropertyData::clearFallback());

    expect($transporter->lastData)->toBe(['fallback_value' => null]);
});

test('update with empty payload sends empty body', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = samplePropertyResponse();

    $service = new AudiencePropertyService($transporter);
    $service->update('p-1', UpdateAudiencePropertyData::empty());

    expect($transporter->lastData)->toBe([]);
});

test('delete hits DELETE audience/properties/{id}', function (): void {
    $transporter = new MockTransporter;

    $service = new AudiencePropertyService($transporter);
    $service->delete('p-1');

    expect($transporter->lastUri)->toBe('audience/properties/p-1');
});
