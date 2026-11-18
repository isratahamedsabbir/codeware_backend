<?php

namespace App\Livewire\Admin\Builders;

use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\BuilderDesign;
use App\Support\AdminActivity;
use App\Support\Locale;
use Livewire\Component;
use Livewire\WithPagination;

/** Admin → Builder: create, open, publish and delete builder designs. */
class Index extends Component
{
    use HasPerPage, WithPagination, WithSearch;

    public string $name = '';

    /** The language the new design is first built in. */
    public string $locale = '';

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    public function mount(): void
    {
        $this->locale = Locale::primary();
    }

    /** @return array<string, string> code => label */
    public function localeOptions(): array
    {
        $options = Locale::active()
            ->mapWithKeys(fn ($language) => [$language->code => $language->native_name ?: $language->name])
            ->all();

        return $options ?: [Locale::primary() => Locale::label(Locale::primary())];
    }

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    public function create()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'locale' => ['required', 'in:'.implode(',', array_keys($this->localeOptions()))],
        ]);

        $design = BuilderDesign::create([
            'name' => trim($this->name),
            'user_id' => auth()->id(),
        ]);

        AdminActivity::log('created', "Builder #{$design->id}: {$design->name}");

        return $this->redirectRoute('admin.builders.edit', ['id' => $design->id, 'locale' => $this->locale]);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'builder-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $design = BuilderDesign::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Builder #{$design->id}: {$design->name}");
            $design->delete();
            $this->dispatch('notify', message: 'Builder deleted');
        }

        $this->deletingId = null;
        $this->dispatch('close-modal', name: 'builder-delete');
    }

    public function render()
    {
        return view('livewire.admin.builders.index', [
            'designs' => BuilderDesign::query()
                ->when($this->search,fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->latest('updated_at')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Builder']);
    }
}
