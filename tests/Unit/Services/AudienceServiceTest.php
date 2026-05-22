<?php

declare(strict_types=1);

use Lettr\Services\Audience\AudienceContactService;
use Lettr\Services\Audience\AudienceListService;
use Lettr\Services\Audience\AudiencePropertyService;
use Lettr\Services\Audience\AudienceSegmentService;
use Lettr\Services\Audience\AudienceTopicService;
use Lettr\Services\AudienceService;
use Tests\Support\MockTransporter;

test('exposes all five sub-services via methods', function (): void {
    $service = new AudienceService(new MockTransporter);

    expect($service->lists())->toBeInstanceOf(AudienceListService::class)
        ->and($service->contacts())->toBeInstanceOf(AudienceContactService::class)
        ->and($service->topics())->toBeInstanceOf(AudienceTopicService::class)
        ->and($service->properties())->toBeInstanceOf(AudiencePropertyService::class)
        ->and($service->segments())->toBeInstanceOf(AudienceSegmentService::class);
});

test('exposes all five sub-services via magic properties', function (): void {
    $service = new AudienceService(new MockTransporter);

    expect($service->lists)->toBeInstanceOf(AudienceListService::class)
        ->and($service->contacts)->toBeInstanceOf(AudienceContactService::class)
        ->and($service->topics)->toBeInstanceOf(AudienceTopicService::class)
        ->and($service->properties)->toBeInstanceOf(AudiencePropertyService::class)
        ->and($service->segments)->toBeInstanceOf(AudienceSegmentService::class);
});

test('caches each sub-service', function (): void {
    $service = new AudienceService(new MockTransporter);

    expect($service->lists())->toBe($service->lists())
        ->and($service->contacts())->toBe($service->contacts())
        ->and($service->topics())->toBe($service->topics())
        ->and($service->properties())->toBe($service->properties())
        ->and($service->segments())->toBe($service->segments());
});

test('throws InvalidArgumentException for unknown sub-service', function (): void {
    $service = new AudienceService(new MockTransporter);

    $service->unknown;
})->throws(InvalidArgumentException::class, 'Unknown audience sub-service: unknown');
