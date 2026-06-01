<?php

declare(strict_types=1);

use Lettr\Client;
use Lettr\Contracts\TransporterContract;

function clientUserAgent(Client $client): string
{
    $property = new ReflectionProperty(Client::class, 'userAgent');

    return $property->getValue($client);
}

test('can create Client instance', function (): void {
    $client = new Client('test-api-key');

    expect($client)->toBeInstanceOf(Client::class);
});

test('implements TransporterContract', function (): void {
    $client = new Client('test-api-key');

    expect($client)->toBeInstanceOf(TransporterContract::class);
});

test('builds a lettr-php User-Agent with the resolved version', function (): void {
    $client = new Client('test-api-key');

    expect(clientUserAgent($client))->toStartWith('lettr-php/');
});

test('appends a wrapping package suffix to the User-Agent', function (): void {
    $client = new Client('test-api-key', 'lettr-laravel/2.2.0');

    expect(clientUserAgent($client))
        ->toStartWith('lettr-php/')
        ->toEndWith(' lettr-laravel/2.2.0');
});

test('omits the suffix segment when none is given', function (): void {
    $client = new Client('test-api-key');

    expect(clientUserAgent($client))->not->toContain(' ');
});

test('strips CR/LF from the suffix to prevent header injection', function (): void {
    $client = new Client('test-api-key', "evil\r\nX-Injected: 1");

    expect(clientUserAgent($client))
        ->not->toContain("\r")
        ->and(clientUserAgent($client))->not->toContain("\n")
        ->and(clientUserAgent($client))->toEndWith(' evilX-Injected: 1');
});
