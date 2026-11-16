<?php

namespace App\Livewire\Admin\Groups;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Group;
use App\Models\Type;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public ?int $typeFilter = null;

    public ?int $deletingId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function toggleStatus(int $id): void
    {
        $group = Group::findOrFail($id);
        $newStatus = $group->status === 'active' ? 'inactive' : 'active';

        $group->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Group: {$group->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Group status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'group-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $group = Group::findOrFail($this->deletingId);

            AdminActivity::log('deleted', "Group: {$group->name}");
            $group->delete();
            $this->dispatch('notify', message: 'Group deleted successfully');
            $this->deletingId = null;
        }

        $this->dispatch('close-modal', name: 'group-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'group-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $groups = Group::whereIn('id', $this->selectedIds)->get();

        foreach ($groups as $group) {
            AdminActivity::log('deleted', "Group: {$group->name}");
            $group->delete();
        }

        $this->dispatch('notify', message: $groups->count().' '.Str::plural('group', $groups->count()).' deleted successfully');
        $this->selectedIds = [];
        $this->dispatch('close-modal', name: 'group-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.groups.index', [
            'groups' => Group::query()
                ->with('type')
                ->withCount('media')
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%")))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->typeFilter, fn ($q) => $q->where('type_id', $this->typeFilter))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
            'types' => Type::selectOptions(),
        ])->layout('layouts.admin', ['title' => 'Groups', 'hidePageHeading' => true]);
    }
}
