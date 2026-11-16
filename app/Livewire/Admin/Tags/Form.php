<?php

namespace App\Livewire\Admin\Tags;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\HasTranslatableFields;
use App\Concerns\WithSearch;
use App\Models\Tag;
use App\Models\Type;
use App\Support\AdminActivity;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class Form extends Component
{
    use HasBulkSelection, HasPerPage, HasTranslatableFields, WithPagination, WithSearch;

    public ?int $tagId = null;

    public array $name = [];

    /**
     * Which pool the tag belongs to — post tags show on the Post form, product
     * tags on the Product form. Required, with no "shared (both)" option: a tag
     * that belonged to neither pool was never really a tag, so every row now
     * picks exactly one (see the add_type_id_to_categories migration).
     */
    #[Validate('required|integer|exists:types,id')]
    public ?int $typeId = null;

    public string $statusFilter = '';

    /** Empty means "every type" — a plain narrowing of the list beside the form. */
    public string $typeFilter = '';

    public ?int $deletingId = null;

    public function mount(?int $id = null): void
    {
        if (! $id) {
            $this->typeId = $this->defaultTypeId();

            return;
        }

        $this->edit($id);
    }

    /** Loads a tag into the form without leaving the page. */
    public function edit(int $id): void
    {
        $tag = Tag::findOrFail($id);

        $this->resetValidation();
        $this->tagId = $id;
        $this->name = [];
        $this->hydrateTranslatable($tag, ['name']);
        $this->typeId = $tag->type_id;
    }

    /** Back to a blank "new tag" form. */
    public function resetForm(): void
    {
        $this->resetValidation();
        $this->reset(['tagId', 'name']);
        $this->typeId = $this->defaultTypeId();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
        ]));

        // Uniqueness is enforced against tag rows only (kind = tag); the column
        // is the JSON path, so the primary locale's value is what's compared —
        // a category or brand sharing the string is fine.
        $rules['name.'.$this->primaryLocale][] = Rule::unique('categories', 'name->'.$this->primaryLocale)
            ->where(fn ($q) => $q->where('kind', Tag::KIND))
            ->ignore($this->tagId);

        $this->validate($rules);

        $creating = $this->tagId === null;

        $data = [
            'name' => $this->translatablePayload('name'),
            'type_id' => $this->typeId,
        ];

        if ($this->tagId) {
            Tag::findOrFail($this->tagId)->update($data);
            $this->dispatch('notify', message: 'Tag updated successfully');
        } else {
            // New tags stay inactive until switched on from the list — status is
            // no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $this->tagId = Tag::create($data)->id;
            $this->dispatch('notify', message: 'Tag created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Tag: {$this->primaryValue('name')}",
        );

        // The list is right beside the form, so a created tag just clears it
        // for the next one.
        if ($creating) {
            $keepType = $this->typeId;
            $this->resetForm();
            $this->typeId = $keepType;
        }
    }

    public function toggleStatus(int $id): void
    {
        $tag = Tag::findOrFail($id);
        $newStatus = $tag->status === 'active' ? 'inactive' : 'active';

        $tag->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Tag: {$tag->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Tag status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'tag-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $tag = Tag::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Tag: {$tag->name}");
            $tag->delete();
            $this->dispatch('notify', message: 'Tag deleted successfully');

            if ($this->tagId === $this->deletingId) {
                $this->resetForm();
            }

            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'tag-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'tag-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $tags = Tag::whereIn('id', $this->selectedIds)->get();

        foreach ($tags as $tag) {
            AdminActivity::log('deleted', "Tag: {$tag->name}");
            $tag->delete();
        }

        if ($this->tagId && $tags->contains('id', $this->tagId)) {
            $this->resetForm();
        }

        $count = $tags->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('tag', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'tag-bulk-delete');
    }

    /**
     * @return Collection<int, Type>
     */
    #[Computed]
    public function typeOptions()
    {
        return Type::selectOptions();
    }

    /**
     * New tags arrive here from the Tags list's "New tag" button and from the
     * inline "create tag" input on the Post and Product forms, so preselect the
     * Post pool — the same default the form used before types were a table.
     */
    private function defaultTypeId(): ?int
    {
        return Type::idFor(Type::POST) ?? Type::idFor(Type::PRODUCT);
    }

    public function render()
    {
        return view('livewire.admin.tags.form', [
            'types' => $this->typeOptions,
            'tags' => Tag::query()
                ->with('type')
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%")))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->typeFilter !== '', fn ($q) => $q->where('type_id', $this->typeFilter))
                ->withCount('posts')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])
            ->layout('layouts.admin', ['title' => $this->tagId ? 'Edit Tag' : 'New Tag']);
    }
}
