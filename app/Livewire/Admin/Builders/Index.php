<?php

namespace App\Livewire\Admin\Builders;

use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\BuilderDesign;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;

/** Admin → Builder: create, open, publish and delete builder designs. */
class Index extends Component
{
    use HasPerPage, WithPagination, WithSearch;

    public string $name = '';

    public ?int $deletingId = null;

    public ?int $viewingId = null;

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
        ]);

        $design = BuilderDesign::create([
            'name' => trim($this->name),
            'user_id' => auth()->id(),
        ]);

        AdminActivity::log('created', "Builder #{$design->id}: {$design->name}");

        return $this->redirectRoute('admin.builders.edit', ['id' => $design->id]);
    }

    /** A copy of a design in every language, under a new name and its own address. */
    public function duplicate(int $id)
    {
        $source = BuilderDesign::findOrFail($id);

        $copy = BuilderDesign::create([
            'name' => mb_substr($source->name.' (copy)', 0, 255),
            'project' => $source->project,
            'html' => $source->html,
            'css' => $source->css,
            'user_id' => auth()->id(),
        ]);

        AdminActivity::log('duplicated', "Builder #{$source->id} → #{$copy->id}: {$copy->name}");
        $this->dispatch('notify', message: 'Builder duplicated');
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
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->latest('updated_at')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Builder']);
    }
}
