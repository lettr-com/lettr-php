<?php

declare(strict_types=1);

use Lettr\Collections\ProjectCollection;
use Lettr\Dto\Project\ListProjectsFilter;
use Lettr\Dto\Project\Project;
use Lettr\Responses\ListProjectsResponse;
use Lettr\Services\ProjectService;
use Tests\Support\MockTransporter;

test('can create ProjectService instance', function (): void {
    $transporter = new MockTransporter;
    $service = new ProjectService($transporter);

    expect($service)->toBeInstanceOf(ProjectService::class);
});

test('list method returns ListProjectsResponse', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'projects' => [
            [
                'id' => 46,
                'name' => 'wefwef',
                'emoji' => '💻',
                'team_id' => 31,
                'created_at' => '2026-01-24T16:34:36+00:00',
                'updated_at' => '2026-01-24T16:34:36+00:00',
            ],
            [
                'id' => 47,
                'name' => 'Another Project',
                'emoji' => '🚀',
                'team_id' => 31,
                'created_at' => '2026-01-25T10:00:00+00:00',
                'updated_at' => '2026-01-25T10:00:00+00:00',
            ],
        ],
        'pagination' => [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 15,
            'total' => 2,
        ],
    ];

    $service = new ProjectService($transporter);
    $response = $service->list();

    expect($transporter->lastUri)->toBe('projects')
        ->and($transporter->lastQuery)->toBe([])
        ->and($response)->toBeInstanceOf(ListProjectsResponse::class)
        ->and($response->projects)->toBeInstanceOf(ProjectCollection::class)
        ->and($response->projects->count())->toBe(2)
        ->and($response->pagination->total)->toBe(2)
        ->and($response->hasMore())->toBeFalse();
});

test('list method with filter', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'projects' => [],
        'pagination' => [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 10,
            'total' => 0,
        ],
    ];

    $service = new ProjectService($transporter);
    $filter = new ListProjectsFilter(perPage: 10, page: 2);
    $service->list($filter);

    expect($transporter->lastUri)->toBe('projects')
        ->and($transporter->lastQuery)->toBe([
            'per_page' => 10,
            'page' => 2,
        ]);
});

test('Project DTO from array', function (): void {
    $project = Project::from([
        'id' => 46,
        'name' => 'Test Project',
        'emoji' => '💻',
        'team_id' => 31,
        'created_at' => '2026-01-24T16:34:36+00:00',
        'updated_at' => '2026-01-24T16:34:36+00:00',
    ]);

    expect($project->id)->toBe(46)
        ->and($project->name)->toBe('Test Project')
        ->and($project->emoji)->toBe('💻')
        ->and($project->teamId)->toBe(31)
        ->and($project->createdAt->toIso8601())->toBe('2026-01-24T16:34:36+00:00')
        ->and($project->updatedAt->toIso8601())->toBe('2026-01-24T16:34:36+00:00');
});

test('ListProjectsFilter fluent API', function (): void {
    $filter = ListProjectsFilter::create()
        ->perPage(20)
        ->page(3);

    expect($filter->perPage)->toBe(20)
        ->and($filter->page)->toBe(3)
        ->and($filter->hasFilters())->toBeTrue()
        ->and($filter->toArray())->toBe([
            'per_page' => 20,
            'page' => 3,
        ]);
});

test('ListProjectsFilter hasFilters returns false when empty', function (): void {
    $filter = ListProjectsFilter::create();

    expect($filter->hasFilters())->toBeFalse()
        ->and($filter->toArray())->toBe([]);
});

test('ProjectCollection methods', function (): void {
    $projects = ProjectCollection::from([
        Project::from([
            'id' => 1,
            'name' => 'Project 1',
            'emoji' => '💻',
            'team_id' => 100,
            'created_at' => '2026-01-01T12:00:00+00:00',
            'updated_at' => '2026-01-01T12:00:00+00:00',
        ]),
        Project::from([
            'id' => 2,
            'name' => 'Project 2',
            'emoji' => '🚀',
            'team_id' => 200,
            'created_at' => '2026-01-02T12:00:00+00:00',
            'updated_at' => '2026-01-02T12:00:00+00:00',
        ]),
    ]);

    expect($projects->count())->toBe(2)
        ->and($projects->isEmpty())->toBeFalse()
        ->and($projects->first()->name)->toBe('Project 1')
        ->and($projects->findById(2)->id)->toBe(2)
        ->and($projects->findById(999))->toBeNull()
        ->and($projects->findByName('Project 2')->id)->toBe(2)
        ->and($projects->findByName('Nonexistent'))->toBeNull();
});

test('ProjectCollection empty', function (): void {
    $projects = ProjectCollection::empty();

    expect($projects->count())->toBe(0)
        ->and($projects->isEmpty())->toBeTrue()
        ->and($projects->first())->toBeNull();
});

test('ProjectPagination has next and previous page', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'projects' => [],
        'pagination' => [
            'current_page' => 2,
            'last_page' => 5,
            'per_page' => 10,
            'total' => 45,
        ],
    ];

    $service = new ProjectService($transporter);
    $response = $service->list();

    expect($response->hasMore())->toBeTrue()
        ->and($response->pagination->hasNextPage())->toBeTrue()
        ->and($response->pagination->hasPreviousPage())->toBeTrue()
        ->and($response->pagination->nextPage())->toBe(3)
        ->and($response->pagination->previousPage())->toBe(1);
});

test('ProjectCollection can be iterated', function (): void {
    $projects = ProjectCollection::from([
        Project::from([
            'id' => 1,
            'name' => 'Project 1',
            'emoji' => '💻',
            'team_id' => 100,
            'created_at' => '2026-01-01T12:00:00+00:00',
            'updated_at' => '2026-01-01T12:00:00+00:00',
        ]),
        Project::from([
            'id' => 2,
            'name' => 'Project 2',
            'emoji' => '🚀',
            'team_id' => 200,
            'created_at' => '2026-01-02T12:00:00+00:00',
            'updated_at' => '2026-01-02T12:00:00+00:00',
        ]),
    ]);

    $ids = [];
    foreach ($projects as $project) {
        $ids[] = $project->id;
    }

    expect($ids)->toBe([1, 2]);
});
