<?php

namespace App\Livewire\Admin\Builders;

use App\Models\BuilderBlock;
use App\Models\BuilderDesign;
use App\Models\MediaLibrary;
use App\Support\Builder\DynamicBlocks;
use App\Support\Locale;
use Livewire\Component;

/**
 * GrapesJS visual builder for one design (`builders`, type 'design').
 *
 * A design stands on its own: it has no page and no SEO. `project` is what
 * GrapesJS reloads, html + css is what its public address prints.
 *
 * One design serves every language. It is built once in the primary language and
 * saved under that locale; the other languages are typed on each text element
 * itself (Settings → "Translations"), see App\Support\Builder\Translations.
 */
class Editor extends Component
{
    public int $designId;

    /** The locale the design is saved under — always the primary one. */
    public string $locale = '';

    public function mount(int $id): void
    {
        $this->designId = BuilderDesign::findOrFail($id)->id;
        $this->locale = Locale::primary();
    }

    public function save(array $project, string $html, string $css): void
    {
        $this->assertLocaleSupported($this->locale);

        BuilderDesign::findOrFail($this->designId)->saveLocale($this->locale, $project, $html, $css);

        $this->dispatch('builder-saved');
    }

    /** Stores the selected component as a reusable block; returns its id so the editor can offer it at once. */
    public function saveBlock(string $name, string $content): int
    {
        $name = trim($name);
        abort_if($name === '' || mb_strlen($name) > 255 || trim($content) === '', 422);

        return BuilderBlock::create(['name' => $name, 'content' => $content, 'user_id' => auth()->id()])->id;
    }

    /** Scaffolds a new dynamic block (class + view) from a name and returns its editor definition. */
    public function createDynamicBlock(string $name): array
    {
        abort_unless(app()->isLocal(), 403, 'Blocks can only be created in the local environment.');

        try {
            return DynamicBlocks::scaffold($name, $this->availableLocales());
        } catch (\InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function renameBlock(int $id, string $name): void
    {
        $name = trim($name);
        abort_if($name === '' || mb_strlen($name) > 255, 422);

        BuilderBlock::whereKey($id)->update(['name' => $name]);
    }

    public function deleteBlock(int $id): void
    {
        BuilderBlock::whereKey($id)->delete();

        $this->dispatch('block-deleted', id: $id);
    }

    public function render()
    {
        $design = BuilderDesign::findOrFail($this->designId);

        return view('livewire.admin.builders.editor', [
            'design' => $design,
            'assets' => MediaLibrary::where('file_type', 'image')->latest()->limit(80)->get()->map->url->filter()->values(),
            'blocks' => BuilderBlock::orderBy('name')->get(['id', 'name', 'content']),
            'project' => $design->projectFor($this->locale),
            'dynamicBlocks' => DynamicBlocks::definitions($this->availableLocales()),
            'locales' => $this->availableLocales(),
        ])->layout('layouts.builder', ['title' => 'Builder']);
    }

    private function assertLocaleSupported(string $locale): void
    {
        abort_unless(in_array($locale, array_column($this->availableLocales(), 'code'), true), 422);
    }

    /** @return array<int, array{code: string, label: string}> */
    private function availableLocales(): array
    {
        $locales = Locale::active()
            ->map(fn ($language) => [
                'code' => $language->code,
                'label' => $language->native_name ?: $language->name,
            ])
            ->values()
            ->all();

        return $locales ?: [[
            'code' => Locale::primary(),
            'label' => Locale::label(Locale::primary()),
        ]];
    }
}
