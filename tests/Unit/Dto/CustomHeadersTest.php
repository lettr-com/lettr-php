<?php

declare(strict_types=1);

use Lettr\Dto\Email\CustomHeaders;
use Lettr\Exceptions\InvalidValueException;

test('can create custom headers from array', function (): void {
    $headers = CustomHeaders::from([
        'List-Unsubscribe' => '<mailto:unsub@example.com>',
        'X-Custom-ID' => 'abc-123',
    ]);

    expect($headers->all())->toBe([
        'List-Unsubscribe' => '<mailto:unsub@example.com>',
        'X-Custom-ID' => 'abc-123',
    ]);
});

test('can create empty custom headers', function (): void {
    $headers = CustomHeaders::empty();

    expect($headers->isEmpty())->toBeTrue()
        ->and($headers->all())->toBe([]);
});

test('can set a header', function (): void {
    $headers = CustomHeaders::empty()
        ->set('X-Custom-ID', 'abc-123');

    expect($headers->has('X-Custom-ID'))->toBeTrue()
        ->and($headers->get('X-Custom-ID'))->toBe('abc-123');
});

test('can get a header with default', function (): void {
    $headers = CustomHeaders::empty();

    expect($headers->get('X-Missing', 'default'))->toBe('default')
        ->and($headers->get('X-Missing'))->toBeNull();
});

test('toArray returns header data', function (): void {
    $headers = CustomHeaders::from(['X-Custom-ID' => 'abc-123']);

    expect($headers->toArray())->toBe(['X-Custom-ID' => 'abc-123']);
});

test('throws exception when exceeding max headers', function (): void {
    $data = [];
    for ($i = 1; $i <= 11; $i++) {
        $data["X-Header-{$i}"] = "value-{$i}";
    }

    CustomHeaders::from($data);
})->throws(InvalidValueException::class, 'Custom headers cannot exceed 10 entries.');

test('throws exception when header value exceeds max length', function (): void {
    CustomHeaders::from([
        'X-Long' => str_repeat('a', 999),
    ]);
})->throws(InvalidValueException::class, 'Custom header value cannot exceed 998 characters.');

test('allows header value at exact max length', function (): void {
    $headers = CustomHeaders::from([
        'X-Long' => str_repeat('a', 998),
    ]);

    expect($headers->has('X-Long'))->toBeTrue();
});

test('set validates after adding header', function (): void {
    $headers = CustomHeaders::empty();
    for ($i = 1; $i <= 10; $i++) {
        $headers = $headers->set("X-Header-{$i}", "value-{$i}");
    }

    expect(fn () => $headers->set('X-Header-11', 'value-11'))
        ->toThrow(InvalidValueException::class);
});
