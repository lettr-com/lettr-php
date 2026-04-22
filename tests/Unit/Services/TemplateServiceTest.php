<?php

declare(strict_types=1);

use Lettr\Collections\TemplateCollection;
use Lettr\Dto\Template\CreatedTemplate;
use Lettr\Dto\Template\CreateTemplateData;
use Lettr\Dto\Template\ListTemplatesFilter;
use Lettr\Dto\Template\MergeTag;
use Lettr\Dto\Template\MergeTagChild;
use Lettr\Dto\Template\Template;
use Lettr\Dto\Template\TemplateDetail;
use Lettr\Dto\Template\UpdatedTemplate;
use Lettr\Dto\Template\UpdateTemplateData;
use Lettr\Responses\GetMergeTagsResponse;
use Lettr\Responses\GetTemplateHtmlResponse;
use Lettr\Responses\ListTemplatesResponse;
use Lettr\Services\TemplateService;
use Tests\Support\MockTransporter;

test('can create TemplateService instance', function (): void {
    $transporter = new MockTransporter;
    $service = new TemplateService($transporter);

    expect($service)->toBeInstanceOf(TemplateService::class);
});

test('list method returns ListTemplatesResponse', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'templates' => [
            [
                'id' => 1,
                'name' => 'Welcome Email',
                'slug' => 'welcome-email',
                'project_id' => 123,
                'folder_id' => null,
                'created_at' => '2024-01-01T12:00:00+00:00',
                'updated_at' => '2024-01-15T12:00:00+00:00',
            ],
            [
                'id' => 2,
                'name' => 'Newsletter',
                'slug' => 'newsletter',
                'project_id' => 123,
                'folder_id' => 5,
                'created_at' => '2024-01-02T12:00:00+00:00',
                'updated_at' => '2024-01-16T12:00:00+00:00',
            ],
        ],
        'pagination' => [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 15,
            'total' => 2,
        ],
    ];

    $service = new TemplateService($transporter);
    $response = $service->list();

    expect($transporter->lastUri)->toBe('templates')
        ->and($transporter->lastQuery)->toBe([])
        ->and($response)->toBeInstanceOf(ListTemplatesResponse::class)
        ->and($response->templates)->toBeInstanceOf(TemplateCollection::class)
        ->and($response->templates->count())->toBe(2)
        ->and($response->pagination->total)->toBe(2)
        ->and($response->hasMore())->toBeFalse();
});

test('list method with filter', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'templates' => [],
        'pagination' => [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 10,
            'total' => 0,
        ],
    ];

    $service = new TemplateService($transporter);
    $filter = new ListTemplatesFilter(projectId: 456, perPage: 10, page: 2);
    $service->list($filter);

    expect($transporter->lastUri)->toBe('templates')
        ->and($transporter->lastQuery)->toBe([
            'project_id' => 456,
            'per_page' => 10,
            'page' => 2,
        ]);
});

test('get method returns TemplateDetail', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'id' => 1,
        'name' => 'Welcome Email',
        'slug' => 'welcome-email',
        'project_id' => 123,
        'folder_id' => null,
        'active_version' => 3,
        'versions_count' => 5,
        'html' => '<html><body><h1>Welcome!</h1></body></html>',
        'json' => '{"tagName":"mj-body","children":[]}',
        'created_at' => '2024-01-01T12:00:00+00:00',
        'updated_at' => '2024-01-15T12:00:00+00:00',
    ];

    $service = new TemplateService($transporter);
    $template = $service->get('welcome-email');

    expect($transporter->lastUri)->toBe('templates/welcome-email')
        ->and($transporter->lastQuery)->toBe([])
        ->and($template)->toBeInstanceOf(TemplateDetail::class)
        ->and($template->id)->toBe(1)
        ->and($template->name)->toBe('Welcome Email')
        ->and($template->slug)->toBe('welcome-email')
        ->and($template->projectId)->toBe(123)
        ->and($template->folderId)->toBeNull()
        ->and($template->activeVersion)->toBe(3)
        ->and($template->versionsCount)->toBe(5)
        ->and($template->html)->toBe('<html><body><h1>Welcome!</h1></body></html>')
        ->and($template->json)->toBe('{"tagName":"mj-body","children":[]}');
});

test('get method with project ID', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'id' => 1,
        'name' => 'Welcome Email',
        'slug' => 'welcome-email',
        'project_id' => 789,
        'folder_id' => null,
        'active_version' => 1,
        'versions_count' => 1,
        'html' => '<html><body>Hello</body></html>',
        'json' => null,
        'created_at' => '2024-01-01T12:00:00+00:00',
        'updated_at' => '2024-01-15T12:00:00+00:00',
    ];

    $service = new TemplateService($transporter);
    $service->get('welcome-email', projectId: 789);

    expect($transporter->lastUri)->toBe('templates/welcome-email')
        ->and($transporter->lastQuery)->toBe(['project_id' => 789]);
});

test('Template DTO from array', function (): void {
    $template = Template::from([
        'id' => 1,
        'name' => 'Test Template',
        'slug' => 'test-template',
        'project_id' => 123,
        'folder_id' => 5,
        'created_at' => '2024-01-01T12:00:00+00:00',
        'updated_at' => '2024-01-15T12:00:00+00:00',
    ]);

    expect($template->id)->toBe(1)
        ->and($template->name)->toBe('Test Template')
        ->and($template->slug)->toBe('test-template')
        ->and($template->projectId)->toBe(123)
        ->and($template->folderId)->toBe(5)
        ->and($template->createdAt->toIso8601())->toBe('2024-01-01T12:00:00+00:00')
        ->and($template->updatedAt->toIso8601())->toBe('2024-01-15T12:00:00+00:00');
});

test('ListTemplatesFilter fluent API', function (): void {
    $filter = ListTemplatesFilter::create()
        ->projectId(123)
        ->perPage(20)
        ->page(3);

    expect($filter->projectId)->toBe(123)
        ->and($filter->perPage)->toBe(20)
        ->and($filter->page)->toBe(3)
        ->and($filter->hasFilters())->toBeTrue()
        ->and($filter->toArray())->toBe([
            'project_id' => 123,
            'per_page' => 20,
            'page' => 3,
        ]);
});

test('ListTemplatesFilter hasFilters returns false when empty', function (): void {
    $filter = ListTemplatesFilter::create();

    expect($filter->hasFilters())->toBeFalse()
        ->and($filter->toArray())->toBe([]);
});

test('TemplateCollection methods', function (): void {
    $templates = TemplateCollection::from([
        Template::from([
            'id' => 1,
            'name' => 'Template 1',
            'slug' => 'template-1',
            'project_id' => 100,
            'folder_id' => null,
            'created_at' => '2024-01-01T12:00:00+00:00',
            'updated_at' => '2024-01-01T12:00:00+00:00',
        ]),
        Template::from([
            'id' => 2,
            'name' => 'Template 2',
            'slug' => 'template-2',
            'project_id' => 200,
            'folder_id' => 5,
            'created_at' => '2024-01-02T12:00:00+00:00',
            'updated_at' => '2024-01-02T12:00:00+00:00',
        ]),
    ]);

    expect($templates->count())->toBe(2)
        ->and($templates->isEmpty())->toBeFalse()
        ->and($templates->first()->slug)->toBe('template-1')
        ->and($templates->findBySlug('template-2')->id)->toBe(2)
        ->and($templates->findBySlug('nonexistent'))->toBeNull()
        ->and($templates->filterByProject(100)->count())->toBe(1)
        ->and($templates->filterByFolder(5)->count())->toBe(1)
        ->and($templates->filterByFolder(null)->count())->toBe(1);
});

test('TemplateCollection empty', function (): void {
    $templates = TemplateCollection::empty();

    expect($templates->count())->toBe(0)
        ->and($templates->isEmpty())->toBeTrue()
        ->and($templates->first())->toBeNull();
});

test('TemplatePagination has next and previous page', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'templates' => [],
        'pagination' => [
            'current_page' => 2,
            'last_page' => 5,
            'per_page' => 10,
            'total' => 45,
        ],
    ];

    $service = new TemplateService($transporter);
    $response = $service->list();

    expect($response->hasMore())->toBeTrue()
        ->and($response->pagination->hasNextPage())->toBeTrue()
        ->and($response->pagination->hasPreviousPage())->toBeTrue()
        ->and($response->pagination->nextPage())->toBe(3)
        ->and($response->pagination->previousPage())->toBe(1);
});

test('create method creates template and returns CreatedTemplate', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'id' => 10,
        'name' => 'New Template',
        'slug' => 'new-template',
        'project_id' => 123,
        'folder_id' => 5,
        'active_version' => 1,
        'merge_tags' => [
            ['key' => 'user_name', 'required' => true],
        ],
        'created_at' => '2024-01-20T12:00:00+00:00',
    ];

    $service = new TemplateService($transporter);
    $data = new CreateTemplateData(
        name: 'New Template',
        projectId: 123,
        folderId: 5,
        html: '<html><body>Hello</body></html>',
        json: '{"blocks":[]}',
    );
    $template = $service->create($data);

    expect($transporter->lastUri)->toBe('templates')
        ->and($transporter->lastData)->toBe([
            'name' => 'New Template',
            'project_id' => 123,
            'folder_id' => 5,
            'html' => '<html><body>Hello</body></html>',
            'json' => '{"blocks":[]}',
        ])
        ->and($template)->toBeInstanceOf(CreatedTemplate::class)
        ->and($template->id)->toBe(10)
        ->and($template->name)->toBe('New Template')
        ->and($template->slug)->toBe('new-template')
        ->and($template->activeVersion)->toBe(1)
        ->and($template->mergeTags)->toHaveCount(1)
        ->and($template->mergeTags[0])->toBeInstanceOf(MergeTag::class);
});

test('create method with minimal data', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'id' => 11,
        'name' => 'Minimal Template',
        'slug' => 'minimal-template',
        'project_id' => 1,
        'folder_id' => 1,
        'active_version' => 1,
        'merge_tags' => [],
        'created_at' => '2024-01-20T12:00:00+00:00',
    ];

    $service = new TemplateService($transporter);
    $data = new CreateTemplateData(name: 'Minimal Template');
    $template = $service->create($data);

    expect($transporter->lastData)->toBe(['name' => 'Minimal Template'])
        ->and($template)->toBeInstanceOf(CreatedTemplate::class)
        ->and($template->name)->toBe('Minimal Template')
        ->and($template->slug)->toBe('minimal-template')
        ->and($template->mergeTags)->toBeEmpty();
});

test('delete method calls correct endpoint', function (): void {
    $transporter = new MockTransporter;
    $service = new TemplateService($transporter);

    $service->delete('my-template');

    expect($transporter->lastUri)->toBe('templates/my-template');
});

test('delete method with project ID', function (): void {
    $transporter = new MockTransporter;
    $service = new TemplateService($transporter);

    $service->delete('my-template', projectId: 456);

    expect($transporter->lastUri)->toBe('templates/my-template?project_id=456');
});

test('CreateTemplateData toArray only includes non-null values', function (): void {
    $data = new CreateTemplateData(
        name: 'Test',
        projectId: 100,
    );

    expect($data->toArray())->toBe([
        'name' => 'Test',
        'project_id' => 100,
    ]);
});

test('getMergeTags returns GetMergeTagsResponse', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'project_id' => 123,
        'template_slug' => 'welcome-email',
        'version' => 2,
        'merge_tags' => [
            [
                'key' => 'user_name',
                'required' => true,
                'type' => 'string',
            ],
            [
                'key' => 'order',
                'required' => false,
                'type' => 'object',
                'children' => [
                    ['key' => 'id', 'type' => 'integer'],
                    ['key' => 'total', 'type' => 'number'],
                ],
            ],
        ],
    ];

    $service = new TemplateService($transporter);
    $response = $service->getMergeTags('welcome-email');

    expect($transporter->lastUri)->toBe('templates/welcome-email/merge-tags')
        ->and($transporter->lastQuery)->toBe([])
        ->and($response)->toBeInstanceOf(GetMergeTagsResponse::class)
        ->and($response->projectId)->toBe(123)
        ->and($response->templateSlug)->toBe('welcome-email')
        ->and($response->version)->toBe(2)
        ->and($response->mergeTags)->toHaveCount(2)
        ->and($response->mergeTags[0])->toBeInstanceOf(MergeTag::class)
        ->and($response->mergeTags[0]->key)->toBe('user_name')
        ->and($response->mergeTags[0]->required)->toBeTrue()
        ->and($response->mergeTags[0]->type)->toBe('string')
        ->and($response->mergeTags[0]->children)->toBeNull()
        ->and($response->mergeTags[1]->key)->toBe('order')
        ->and($response->mergeTags[1]->required)->toBeFalse()
        ->and($response->mergeTags[1]->children)->toHaveCount(2)
        ->and($response->mergeTags[1]->children[0])->toBeInstanceOf(MergeTagChild::class)
        ->and($response->mergeTags[1]->children[0]->key)->toBe('id')
        ->and($response->mergeTags[1]->children[0]->type)->toBe('integer');
});

test('getMergeTags with project ID and version', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'project_id' => 456,
        'template_slug' => 'newsletter',
        'version' => 5,
        'merge_tags' => [],
    ];

    $service = new TemplateService($transporter);
    $service->getMergeTags('newsletter', projectId: 456, version: 5);

    expect($transporter->lastUri)->toBe('templates/newsletter/merge-tags')
        ->and($transporter->lastQuery)->toBe([
            'project_id' => 456,
            'version' => 5,
        ]);
});

test('MergeTag from array', function (): void {
    $tag = MergeTag::from([
        'key' => 'test_key',
        'required' => true,
        'type' => 'text',
        'children' => [
            ['key' => 'child1', 'type' => 'number'],
        ],
    ]);

    expect($tag->key)->toBe('test_key')
        ->and($tag->required)->toBeTrue()
        ->and($tag->type)->toBe('text')
        ->and($tag->children)->toHaveCount(1)
        ->and($tag->children[0])->toBeInstanceOf(MergeTagChild::class)
        ->and($tag->children[0]->key)->toBe('child1')
        ->and($tag->children[0]->type)->toBe('number');
});

test('MergeTag from array with minimal data', function (): void {
    $tag = MergeTag::from([
        'key' => 'simple_key',
        'required' => false,
    ]);

    expect($tag->key)->toBe('simple_key')
        ->and($tag->required)->toBeFalse()
        ->and($tag->type)->toBeNull()
        ->and($tag->children)->toBeNull();
});

test('update PUT templates/{slug} and returns UpdatedTemplate', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'id' => 10,
        'name' => 'Renamed Template',
        'slug' => 'welcome-email',
        'project_id' => 123,
        'folder_id' => 5,
        'active_version' => 4,
        'merge_tags' => [
            ['key' => 'first_name', 'required' => true, 'type' => 'text'],
        ],
        'created_at' => '2024-01-01T12:00:00+00:00',
        'updated_at' => '2026-04-18T12:00:00+00:00',
    ];

    $service = new TemplateService($transporter);
    $data = new UpdateTemplateData(
        name: 'Renamed Template',
        html: '<html><body>New</body></html>',
    );
    $template = $service->update('welcome-email', $data);

    expect($transporter->lastUri)->toBe('templates/welcome-email')
        ->and($transporter->lastData)->toBe([
            'name' => 'Renamed Template',
            'html' => '<html><body>New</body></html>',
        ])
        ->and($template)->toBeInstanceOf(UpdatedTemplate::class)
        ->and($template->name)->toBe('Renamed Template')
        ->and($template->activeVersion)->toBe(4)
        ->and($template->mergeTags[0]->key)->toBe('first_name')
        ->and($template->mergeTags[0]->type)->toBe('text');
});

test('getHtml GET templates/html with project and slug', function (): void {
    $transporter = new MockTransporter;
    $transporter->response = [
        'html' => '<html><body>Hello {{FIRST_NAME}}</body></html>',
        'subject' => 'Hello there',
        'merge_tags' => [
            ['key' => 'FIRST_NAME', 'required' => true, 'type' => 'text'],
        ],
    ];

    $service = new TemplateService($transporter);
    $response = $service->getHtml(projectId: 123, slug: 'welcome-email');

    expect($transporter->lastUri)->toBe('templates/html')
        ->and($transporter->lastQuery)->toBe([
            'project_id' => 123,
            'slug' => 'welcome-email',
        ])
        ->and($response)->toBeInstanceOf(GetTemplateHtmlResponse::class)
        ->and($response->html)->toContain('{{FIRST_NAME}}')
        ->and($response->subject)->toBe('Hello there')
        ->and($response->mergeTags[0]->key)->toBe('FIRST_NAME')
        ->and($response->mergeTags[0]->type)->toBe('text');
});
