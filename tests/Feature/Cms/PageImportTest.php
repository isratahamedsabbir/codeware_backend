<?php

use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/**
 * Builds a CSV the way the Pages screen's export writes one. Rows are keyed by
 * header name so a test only spells out the columns it cares about.
 *
 * @param  list<array<string, string|int>>  $rows
 * @param  list<string>|null  $header
 */
function pageImportCsv(array $rows, ?array $header = null): string
{
    $header ??= [
        'ID', 'Title (EN)', 'Title (BN)', 'Slug', 'Type', 'Template',
        'Status', 'Sort Order', 'Created At',
    ];

    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, $header);

    foreach ($rows as $row) {
        fputcsv($handle, array_map(fn (string $column) => (string) ($row[$column] ?? ''), $header));
    }

    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return $csv;
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);

    $this->import = fn (string $csv, bool $updateExisting = false) => Livewire::test(PagesIndex::class)
        ->set('importFile', UploadedFile::fake()->createWithContent('pages.csv', $csv))
        ->set('importUpdateExisting', $updateExisting)
        ->call('importCsv');
});

it('imports a new standalone page with its translations, template, status and order', function () {
    $component = ($this->import)(pageImportCsv([[
        'Title (EN)' => 'Our Team',
        'Title (BN)' => 'আমাদের দল',
        'Slug' => 'our-team',
        'Type' => 'page',
        'Template' => 'default',
        'Status' => 'Active',
        'Sort Order' => '4',
    ]]));

    $page = Page::sole();

    expect($component->get('importResult'))->toMatchArray([
        'created' => 1,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'warnings' => [],
    ])
        ->and($page->getTranslation('title', 'en'))->toBe('Our Team')
        ->and($page->getTranslation('title', 'bn'))->toBe('আমাদের দল')
        ->and($page->slug)->toBe('our-team')
        ->and($page->type)->toBe('page')
        ->and($page->template)->toBe('default')
        ->and($page->status)->toBe('active')
        ->and($page->sort_order)->toBe(4)
        ->and($page->user_id)->toBe($this->admin->id);
});

it('creates an inactive puck page with a generated slug and the next sort order by default', function () {
    Page::factory()->create(['sort_order' => 7]);

    $component = ($this->import)(pageImportCsv([[
        'Title (EN)' => 'A New Page',
        'Slug' => '',
        'Status' => '',
    ]]));

    $page = Page::whereJsonContains('title->en', 'A New Page')->sole();

    expect($component->get('importResult')['created'])->toBe(1)
        ->and($page->status)->toBe('inactive')
        ->and($page->slug)->toBe('a-new-page')
        ->and($page->type)->toBe('page')
        ->and($page->template)->toBe('puck')
        ->and($page->sort_order)->toBe(8);
});

it('updates an existing page matched by id and keeps blank optional cells', function () {
    $page = Page::factory()->published()->create([
        'title' => ['en' => 'Old Page', 'bn' => 'পুরনো পৃষ্ঠা'],
        'slug' => 'old-page',
        'template' => 'default',
        'sort_order' => 3,
    ]);

    $component = ($this->import)(
        pageImportCsv([[
            'ID' => $page->id,
            'Title (EN)' => 'Renamed Page',
        ]], ['ID', 'Title (EN)']),
        true,
    );

    $page->refresh();

    expect($component->get('importResult'))->toMatchArray(['created' => 0, 'updated' => 1, 'skipped' => 0])
        ->and($page->getTranslation('title', 'en'))->toBe('Renamed Page')
        ->and($page->getTranslation('title', 'bn'))->toBe('পুরনো পৃষ্ঠা')
        ->and($page->slug)->toBe('old-page')
        ->and($page->template)->toBe('default')
        ->and($page->status)->toBe('active')
        ->and($page->sort_order)->toBe(3);
});

it('finds an existing page by slug when the id column is blank', function () {
    $page = Page::factory()->create([
        'title' => ['en' => 'Legacy Page', 'bn' => ''],
        'slug' => 'legacy-page',
    ]);

    $component = ($this->import)(pageImportCsv([[
        'Slug' => 'legacy-page',
        'Title (EN)' => 'Legacy Page Renamed',
        'Status' => 'Inactive',
    ]]), true);

    $page->refresh();

    expect($component->get('importResult')['updated'])->toBe(1)
        ->and($page->getTranslation('title', 'en'))->toBe('Legacy Page Renamed')
        ->and($page->status)->toBe('inactive');
});

it('skips rows for existing pages when the update toggle is off', function () {
    $page = Page::factory()->create([
        'title' => ['en' => 'Keep Me', 'bn' => ''],
        'slug' => 'keep-me',
    ]);

    $component = ($this->import)(pageImportCsv([[
        'ID' => $page->id,
        'Title (EN)' => 'Should Not Replace',
    ]]));

    expect($component->get('importResult'))->toMatchArray(['created' => 0, 'updated' => 0, 'skipped' => 1])
        ->and($page->refresh()->getTranslation('title', 'en'))->toBe('Keep Me');
});

it('reports a row-level error but still imports the other rows', function () {
    $component = ($this->import)(pageImportCsv([
        ['Title (EN)' => '', 'Slug' => 'orphan-row'],
        ['Title (EN)' => 'Valid Page', 'Slug' => 'valid-page'],
    ]));

    $result = $component->get('importResult');

    expect($result['created'])->toBe(1)
        ->and($result['skipped'])->toBe(1)
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0])->toContain('Line 2: Title (EN) is required.')
        ->and(Page::whereJsonContains('title->en', 'Valid Page')->exists())->toBeTrue();
});

it('rejects creating a linked page from a sheet', function () {
    $component = ($this->import)(pageImportCsv([[
        'Title (EN)' => 'A Product Page',
        'Type' => 'product',
    ]]));

    $result = $component->get('importResult');

    expect($result['created'])->toBe(0)
        ->and($result['skipped'])->toBe(1)
        ->and($result['errors'][0])->toContain('Only standalone pages')
        ->and(Page::count())->toBe(0);
});

it('warns when an existing linked page’s type column differs, keeping its type', function () {
    $page = Page::factory()->create([
        'type' => 'post',
        'title' => ['en' => 'Post Companion', 'bn' => ''],
        'slug' => 'post-companion',
    ]);

    $component = ($this->import)(pageImportCsv([[
        'ID' => $page->id,
        'Title (EN)' => 'Post Companion Renamed',
        'Type' => 'page',
    ]]), true);

    $result = $component->get('importResult');

    expect($result['updated'])->toBe(1)
        ->and($result['warnings'])->toHaveCount(1)
        ->and($result['warnings'][0])->toContain('was ignored')
        ->and($page->refresh()->type)->toBe('post')
        ->and($page->slug)->toBe('post-companion');
});

it('appends a suffix when an imported slug is already taken by another page', function () {
    Page::factory()->create(['slug' => 'contact']);
    $target = Page::factory()->create(['slug' => 'get-in-touch']);

    ($this->import)(pageImportCsv([[
        'ID' => $target->id,
        'Title (EN)' => 'Get In Touch',
        'Slug' => 'contact',
    ]]), true);

    expect($target->refresh()->slug)->toBe('contact-2');
});

it('accepts a header with a UTF-8 BOM and different casing', function () {
    $csv = "\xEF\xBB\xBFtitle (en),title (bn)\nBom Page,বম পৃষ্ঠা\n";

    $component = ($this->import)($csv);

    expect($component->get('importResult')['created'])->toBe(1)
        ->and(Page::sole()->getTranslation('title', 'en'))->toBe('Bom Page')
        ->and(Page::sole()->getTranslation('title', 'bn'))->toBe('বম পৃষ্ঠা');
});

it('requires a CSV file before importing', function () {
    Livewire::test(PagesIndex::class)
        ->call('importCsv')
        ->assertHasErrors(['importFile']);
});

it('renders the import form only while its modal is open', function () {
    $component = Livewire::test(PagesIndex::class)
        ->assertSet('importModalOpen', false)
        ->assertDontSee('Overwrite existing pages')
        ->call('openImport')
        ->assertSet('importModalOpen', true)
        ->assertSee('Overwrite existing pages')
        ->assertDispatched('open-modal', name: 'page-import')
        ->call('closeImport')
        ->assertSet('importModalOpen', false)
        ->assertDispatched('close-modal', name: 'page-import');
});
