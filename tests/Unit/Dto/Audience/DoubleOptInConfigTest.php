<?php

declare(strict_types=1);

use Lettr\Dto\Audience\DoubleOptInConfig;

test('toArray emits snake_case keys', function (): void {
    $config = new DoubleOptInConfig(
        from: 'team@example.com',
        subject: 'Confirm',
        templateSlug: 'confirm',
        redirectUrl: 'https://example.com/done',
        fromName: 'Team',
    );

    expect($config->toArray())->toBe([
        'from' => 'team@example.com',
        'subject' => 'Confirm',
        'template_slug' => 'confirm',
        'redirect_url' => 'https://example.com/done',
        'from_name' => 'Team',
    ]);
});

test('toArray omits from_name when null', function (): void {
    $config = new DoubleOptInConfig(
        from: 'team@example.com',
        subject: 'Confirm',
        templateSlug: 'confirm',
        redirectUrl: 'https://example.com/done',
    );

    expect($config->toArray())->toBe([
        'from' => 'team@example.com',
        'subject' => 'Confirm',
        'template_slug' => 'confirm',
        'redirect_url' => 'https://example.com/done',
    ])->and($config->toArray())->not->toHaveKey('from_name');
});
