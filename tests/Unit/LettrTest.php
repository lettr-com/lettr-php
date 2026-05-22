<?php

declare(strict_types=1);

use Lettr\Lettr;
use Lettr\Services\AudienceService;
use Lettr\Services\DomainService;
use Lettr\Services\EmailService;
use Lettr\Services\ProjectService;
use Lettr\Services\TemplateService;
use Lettr\Services\WebhookService;

test('can create Lettr instance with client method', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr)->toBeInstanceOf(Lettr::class);
});

test('can access emails service via method', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->emails())->toBeInstanceOf(EmailService::class);
});

test('can access emails service via property', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->emails)->toBeInstanceOf(EmailService::class);
});

test('can access domains service via method', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->domains())->toBeInstanceOf(DomainService::class);
});

test('can access domains service via property', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->domains)->toBeInstanceOf(DomainService::class);
});

test('can access webhooks service via method', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->webhooks())->toBeInstanceOf(WebhookService::class);
});

test('can access webhooks service via property', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->webhooks)->toBeInstanceOf(WebhookService::class);
});

test('emails service is cached', function (): void {
    $lettr = Lettr::client('test-api-key');

    $emails1 = $lettr->emails();
    $emails2 = $lettr->emails();

    expect($emails1)->toBe($emails2);
});

test('domains service is cached', function (): void {
    $lettr = Lettr::client('test-api-key');

    $domains1 = $lettr->domains();
    $domains2 = $lettr->domains();

    expect($domains1)->toBe($domains2);
});

test('webhooks service is cached', function (): void {
    $lettr = Lettr::client('test-api-key');

    $webhooks1 = $lettr->webhooks();
    $webhooks2 = $lettr->webhooks();

    expect($webhooks1)->toBe($webhooks2);
});

test('can access templates service via method', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->templates())->toBeInstanceOf(TemplateService::class);
});

test('can access templates service via property', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->templates)->toBeInstanceOf(TemplateService::class);
});

test('templates service is cached', function (): void {
    $lettr = Lettr::client('test-api-key');

    $templates1 = $lettr->templates();
    $templates2 = $lettr->templates();

    expect($templates1)->toBe($templates2);
});

test('can access projects service via method', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->projects())->toBeInstanceOf(ProjectService::class);
});

test('can access projects service via property', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->projects)->toBeInstanceOf(ProjectService::class);
});

test('projects service is cached', function (): void {
    $lettr = Lettr::client('test-api-key');

    $projects1 = $lettr->projects();
    $projects2 = $lettr->projects();

    expect($projects1)->toBe($projects2);
});

test('can access audience service via method', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->audience())->toBeInstanceOf(AudienceService::class);
});

test('can access audience service via property', function (): void {
    $lettr = Lettr::client('test-api-key');

    expect($lettr->audience)->toBeInstanceOf(AudienceService::class);
});

test('audience service is cached', function (): void {
    $lettr = Lettr::client('test-api-key');

    $audience1 = $lettr->audience();
    $audience2 = $lettr->audience();

    expect($audience1)->toBe($audience2);
});

test('throws exception for unknown service', function (): void {
    $lettr = Lettr::client('test-api-key');

    $lettr->unknownService;
})->throws(InvalidArgumentException::class, 'Unknown service: unknownService');

test('has correct version constant', function (): void {
    expect(Lettr::VERSION)->toBe('2.1.0');
});

test('has correct base url constant', function (): void {
    expect(Lettr::BASE_URL)->toBe('https://app.lettr.com/api/');
});
