<?php

namespace App\Livewire\Admin\Groups;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\HasTranslatableFields;
use App\Concerns\WithSearch;
use App\Models\Group;
use App\Models\Type;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Form and list on one screen: the form on the left creates or edits a group,
 * the datatable beside it lists every group (edit loads a row into the form).
 */
class Form extends Component
{
    use HasBulkSelection, HasPerPage, HasTranslatableFields, WithPagination, WithSearch;

    public ?int $groupId = null;

    public array $name = [];

    public ?int $typeId = null;

    public string $statusFilter = '';

    public ?int $typeFilter = null;

    public ?int $deletingId = null;

    public function mount(?int $id = null): void
    {
        if (! $id) {
            // Arriving from a link with ?type=<id> preselects that type.
            $queryType = request()->query('type');
            if (is_string($queryType) && ctype_digit($queryType) && Type::whereKey($queryType)->exists()) {
                $this->typeId = (int) $queryType;
            }

            return;
        }

        $this->edit($id);
    }

    /** Loads a group into the form without leaving the page. */
    public function edit(int $id): void
    {
        $group = Group::findOrFail($id);

        $this->resetValidation();
        $this->groupId = $id;
        $this->typeId = $group->type_id;
        $this->name = [];
        $this->hydrateTranslatable($group, ['name']);
    }

    /** Back to a blank "new group" form. */
    public function resetForm(): void
    {
        $this->resetValidation();
        $this->reset(['groupId', 'name', 'typeId']);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function save(): void
    {
        $this->validate(array_merge($this->translatableRules([
            'name' => 'required|string|max:255',
        ]), [
            'typeId' => ['required', 'integer', 'exists:types,id'],
        ]));

        $data = [
            'name' => $this->translatablePayload('name'),
            'type_id' => $this->typeId,
        ];

        $creating = $this->groupId === null;

        if ($this->groupId) {
            Group::findOrFail($this->groupId)->update($data);
            $this->dispatch('notify', message: 'Group updated successfully');
        } else {
            $data['status'] = 'active';
            $this->groupId = Group::create($data)->id;
            $this->dispatch('notify', message: 'Group created successfully');
        }

        AdminActivity::log($creating ? 'created' : 'updated', "Group: {$this->primaryValue('name')}");

        // The list is right beside the form, so a created group just clears it
        // for the next one (keeping the type, which is usually the same).
        if ($creating) {
            $keepType = $this->typeId;
            $this->resetForm();
            $this->typeId = $keepType;
        }
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

            if ($this->groupId === $this->deletingId) {
                $this->resetForm();
            }

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

        if ($this->groupId && $groups->contains('id', $this->groupId)) {
            $this->resetForm();
        }

        $this->dispatch('notify', message: $groups->count().' '.Str::plural('group', $groups->count()).' deleted successfully');
        $this->selectedIds = [];
        $this->dispatch('close-modal', name: 'group-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.groups.form', [
            'types' => Type::selectOptions(),
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
        ])->layout('layouts.admin', ['title' => $this->groupId ? 'Edit Group' : 'New Group']);
    }
}
