<?php

declare(strict_types=1);

use Lettr\Collections\DomainCollection;
use Lettr\Dto\Domain\CreateDomainData;
use Lettr\Dto\Domain\CreateDomainResponse;
use Lettr\Dto\Domain\Domain;
use Lettr\Dto\Domain\DomainDetail;
use Lettr\Dto\Domain\DomainVerification;
use Lettr\Enums\DnsStatus;
use Lettr\Enums\DomainStatus;
use Lettr\Services\DomainService;
use Lettr\ValueObjects\DomainName;
use Tests\Support\MockTransporter;

test('list returns DomainCollection with all DomainListItemView fields', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'domains' => [
            [
                'domain' => 'example.com',
                'status' => 'approved',
                'status_label' => 'Approved',
                'can_send' => true,
                'cname_status' => 'valid',
                'dkim_status' => 'valid',
                'created_at' => '2024-01-15T10:30:00+00:00',
                'updated_at' => '2024-01-16T14:45:00+00:00',
            ],
            [
                'domain' => 'pending.test',
                'status' => 'pending',
                'status_label' => 'Pending Review',
                'can_send' => false,
                'cname_status' => null,
                'dkim_status' => null,
                'created_at' => '2024-02-01T09:00:00+00:00',
                'updated_at' => '2024-02-01T09:00:00+00:00',
            ],
        ],
    ];

    $service = new DomainService($transporter);
    $domains = $service->list();

    expect($transporter->lastUri)->toBe('domains')
        ->and($domains)->toBeInstanceOf(DomainCollection::class)
        ->and($domains->count())->toBe(2);

    [$first, $second] = [$domains->all()[0], $domains->all()[1]];

    expect($first)->toBeInstanceOf(Domain::class)
        ->and((string) $first->domain)->toBe('example.com')
        ->and($first->status)->toBe(DomainStatus::Approved)
        ->and($first->statusLabel)->toBe('Approved')
        ->and($first->canSend)->toBeTrue()
        ->and($first->cnameStatus)->toBe(DnsStatus::Valid)
        ->and($first->dkimStatus)->toBe(DnsStatus::Valid)
        ->and($first->updatedAt->toIso8601())->toBe('2024-01-16T14:45:00+00:00')
        ->and($first->isVerified())->toBeTrue();

    expect($second->cnameStatus)->toBeNull()
        ->and($second->dkimStatus)->toBeNull()
        ->and($second->canSend)->toBeFalse()
        ->and($second->isVerified())->toBeFalse()
        ->and($second->needsDnsConfiguration())->toBeTrue();
});

test('create POSTs domains and returns CreateDomainResponse with dkim', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'domain' => 'example.com',
        'status' => 'pending',
        'status_label' => 'Pending Review',
        'dkim' => [
            'selector' => 'scph0226',
            'public' => 'MIGfMA0GCSq...',
            'headers' => 'from:to:subject:date',
            'signing_domain' => 'example.com',
        ],
    ];

    $service = new DomainService($transporter);
    $response = $service->create(new DomainName('example.com'));

    expect($transporter->lastUri)->toBe('domains')
        ->and($transporter->lastData)->toBe(['domain' => 'example.com'])
        ->and($response)->toBeInstanceOf(CreateDomainResponse::class)
        ->and((string) $response->domain)->toBe('example.com')
        ->and($response->status)->toBe(DomainStatus::Pending)
        ->and($response->statusLabel)->toBe('Pending Review')
        ->and($response->dkim?->selector)->toBe('scph0226')
        ->and($response->dkim?->signingDomain)->toBe('example.com');
});

test('create accepts string, DomainName, or CreateDomainData', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'domain' => 'acme.test',
        'status' => 'pending',
        'status_label' => 'Pending Review',
    ];

    $service = new DomainService($transporter);
    $service->create('acme.test');
    expect($transporter->lastData)->toBe(['domain' => 'acme.test']);

    $service->create(new DomainName('beta.test'));
    expect($transporter->lastData)->toBe(['domain' => 'beta.test']);

    $service->create(CreateDomainData::from('gamma.test'));
    expect($transporter->lastData)->toBe(['domain' => 'gamma.test']);
});

test('get returns DomainDetail with spf_status, is_primary_domain, dns_provider', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'domain' => 'example.com',
        'status' => 'approved',
        'status_label' => 'Approved',
        'can_send' => true,
        'cname_status' => 'valid',
        'dkim_status' => 'valid',
        'dmarc_status' => 'valid',
        'spf_status' => 'valid',
        'is_primary_domain' => false,
        'tracking_domain' => 'tracking.example.com',
        'dns' => [
            'dkim' => [
                'selector' => 'scph0226',
                'public' => 'MIGfMA0...',
                'headers' => 'from:to:subject',
                'signing_domain' => 'example.com',
            ],
        ],
        'dns_provider' => [
            'provider' => 'cloudflare',
            'provider_label' => 'Cloudflare',
            'nameservers' => ['ns1.cloudflare.com', 'ns2.cloudflare.com'],
            'error' => null,
        ],
        'created_at' => '2024-01-15T10:30:00+00:00',
        'updated_at' => '2024-01-16T14:45:00+00:00',
    ];

    $service = new DomainService($transporter);
    $detail = $service->get('example.com');

    expect($transporter->lastUri)->toBe('domains/example.com')
        ->and($detail)->toBeInstanceOf(DomainDetail::class)
        ->and($detail->statusLabel)->toBe('Approved')
        ->and($detail->spfStatus)->toBe(DnsStatus::Valid)
        ->and($detail->isPrimaryDomain)->toBeFalse()
        ->and($detail->trackingDomain)->toBe('tracking.example.com')
        ->and($detail->dkim?->selector)->toBe('scph0226')
        ->and($detail->dnsProvider?->provider)->toBe('cloudflare')
        ->and($detail->dnsProvider?->providerLabel)->toBe('Cloudflare')
        ->and($detail->dnsProvider?->nameservers)->toBe(['ns1.cloudflare.com', 'ns2.cloudflare.com'])
        ->and($detail->dnsProvider?->error)->toBeNull()
        ->and($detail->isVerified())->toBeTrue();
});

test('get accepts DomainName value object', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'domain' => 'example.com',
        'status' => 'approved',
        'status_label' => 'Approved',
        'can_send' => true,
        'created_at' => '2024-01-15T10:30:00+00:00',
        'updated_at' => '2024-01-16T14:45:00+00:00',
    ];

    $service = new DomainService($transporter);
    $service->get(new DomainName('example.com'));

    expect($transporter->lastUri)->toBe('domains/example.com');
});

test('delete hits DELETE /domains/{domain}', function (): void {
    $transporter = new MockTransporter;
    $service = new DomainService($transporter);

    $service->delete('example.com');

    expect($transporter->lastUri)->toBe('domains/example.com');
});

test('verify on a primary domain returns cname_status=not_applicable', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'domain' => 'example.com',
        'dkim_status' => 'valid',
        'cname_status' => 'not_applicable',
        'dmarc_status' => 'valid',
        'spf_status' => 'valid',
        'is_primary_domain' => true,
        'ownership_verified' => 'true',
    ];

    $service = new DomainService($transporter);
    $verification = $service->verify('example.com');

    expect($transporter->lastUri)->toBe('domains/example.com/verify')
        ->and($verification)->toBeInstanceOf(DomainVerification::class)
        ->and($verification->cnameStatus)->toBe(DnsStatus::NotApplicable)
        ->and($verification->isPrimaryDomain)->toBeTrue()
        ->and($verification->ownershipVerified)->toBe('true')
        ->and($verification->isFullyVerified())->toBeTrue()
        ->and($verification->hasErrors())->toBeFalse();
});

test('verify with DMARC failure exposes dns errors and dmarc sub-object', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'domain' => 'example.com',
        'dkim_status' => 'valid',
        'cname_status' => 'valid',
        'dmarc_status' => 'missing',
        'spf_status' => 'unverified',
        'is_primary_domain' => false,
        'ownership_verified' => null,
        'dns' => [
            'dkim_record' => 'v=DKIM1; k=rsa; p=...',
            'cname_record' => 'eu.sparkpostmail.com',
            'dmarc_record' => null,
            'spf_record' => null,
            'dkim_error' => null,
            'cname_error' => null,
            'dmarc_error' => 'No DMARC record found for example.com or its parent domain.',
            'spf_error' => null,
        ],
        'dmarc' => [
            'is_valid' => false,
            'status' => 'missing',
            'found_at_domain' => null,
            'record' => null,
            'policy' => null,
            'subdomain_policy' => null,
            'error' => 'No DMARC record found for example.com or its parent domain.',
            'covered_by_parent_policy' => false,
        ],
        'spf' => [
            'is_valid' => false,
            'status' => 'unverified',
            'record' => null,
            'error' => null,
            'includes_sparkpost' => false,
        ],
    ];

    $service = new DomainService($transporter);
    $verification = $service->verify('example.com');

    expect($verification->dmarcStatus)->toBe(DnsStatus::Missing)
        ->and($verification->isFullyVerified())->toBeFalse()
        ->and($verification->hasErrors())->toBeTrue()
        ->and($verification->errors())->toBe([
            'dmarc' => 'No DMARC record found for example.com or its parent domain.',
        ])
        ->and($verification->dmarc?->isValid)->toBeFalse()
        ->and($verification->dmarc?->error)->toContain('No DMARC record')
        ->and($verification->dmarc?->coveredByParentPolicy)->toBeFalse()
        ->and($verification->spf?->isValid)->toBeFalse()
        ->and($verification->spf?->includesSparkpost)->toBeFalse()
        ->and($verification->dns?->dkimRecord)->toBe('v=DKIM1; k=rsa; p=...')
        ->and($verification->dns?->dmarcError)->toContain('No DMARC');
});

test('verify accepts DomainName value object', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'domain' => 'example.com',
        'dkim_status' => 'valid',
        'cname_status' => 'valid',
        'dmarc_status' => 'valid',
        'spf_status' => 'valid',
        'is_primary_domain' => false,
    ];

    $service = new DomainService($transporter);
    $service->verify(new DomainName('example.com'));

    expect($transporter->lastUri)->toBe('domains/example.com/verify');
});
