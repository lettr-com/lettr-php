<?php

declare(strict_types=1);

use Lettr\ValueObjects\ContactProperties;

test('get returns value when key exists', function (): void {
    $props = ContactProperties::from(['plan' => 'pro', 'name' => 'Alice']);

    expect($props->get('plan'))->toBe('pro')
        ->and($props->get('name'))->toBe('Alice');
});

test('get returns null for missing key', function (): void {
    $props = ContactProperties::from(['plan' => 'pro']);

    expect($props->get('missing'))->toBeNull();
});

test('has reflects key presence', function (): void {
    $props = ContactProperties::from(['plan' => 'pro']);

    expect($props->has('plan'))->toBeTrue()
        ->and($props->has('missing'))->toBeFalse();
});

test('all returns the underlying array', function (): void {
    $props = ContactProperties::from(['plan' => 'pro', 'name' => 'Alice']);

    expect($props->all())->toBe(['plan' => 'pro', 'name' => 'Alice']);
});

test('count + isEmpty + iteration', function (): void {
    $props = ContactProperties::from(['plan' => 'pro', 'name' => 'Alice']);

    expect($props->count())->toBe(2)
        ->and($props->isEmpty())->toBeFalse()
        ->and(iterator_to_array($props))->toBe(['plan' => 'pro', 'name' => 'Alice']);
});

test('empty constructor', function (): void {
    $props = ContactProperties::empty();

    expect($props->count())->toBe(0)
        ->and($props->isEmpty())->toBeTrue()
        ->and($props->all())->toBe([]);
});

test('json_encode preserves string keys as a JSON object', function (): void {
    $props = ContactProperties::from(['first_name' => 'SDK', 'plan' => 'pro']);

    expect(json_encode($props))->toBe('{"first_name":"SDK","plan":"pro"}');
});

test('json_encode an empty ContactProperties yields {} not []', function (): void {
    expect(json_encode(ContactProperties::empty(), JSON_FORCE_OBJECT))->toBe('{}');
});
