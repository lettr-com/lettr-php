<?php

declare(strict_types=1);

use Lettr\Collections\AudienceListCollection;
use Lettr\Dto\Audience\AudienceList;

test('json_encode emits a positional JSON array of items', function (): void {
    $collection = AudienceListCollection::from([
        new AudienceList('11111111-1111-1111-1111-111111111111', 'Newsletter', 42),
        new AudienceList('22222222-2222-2222-2222-222222222222', 'VIP', 7),
    ]);

    expect(json_decode(json_encode($collection), true))->toBe([
        ['id' => '11111111-1111-1111-1111-111111111111', 'name' => 'Newsletter', 'contactsCount' => 42],
        ['id' => '22222222-2222-2222-2222-222222222222', 'name' => 'VIP', 'contactsCount' => 7],
    ]);
});

test('json_encode an empty collection yields []', function (): void {
    expect(json_encode(AudienceListCollection::empty()))->toBe('[]');
});
