<?php

namespace App\Livewire\Admin\Types;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\HasTranslatableFields;
use App\Concerns\WithSearch;
use App\Models\Type;
use App\Support\AdminActivity;
use App\Support\Slug;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Form extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;
    use HasTranslatableFields;

    public ?int $typeId = null;

    public array $name = [];

    public string $slug = '';

    /**
     * The last auto-generated slug value, so we know whether the admin has
     * manually diverged from it — see updated().
     */
    public string $autoSlug = '';

    public function mount(?int $id = null): void
    {
        if (! $id) {
            return;
        }

        $type = Type::findOrFail($id);
        $this->typeId = $id;
        $this->hydrateTranslatable($type, ['name']);
        $this->slug = $type->slug;
    }

    /**
     * Loads a type into the form beside the table, so editing never leaves the
     * page. The table is the list; the form is the editor.
     */
    public function edit(int $id): void
    {
        $type = Type::findOrFail($id);

        $this->resetValidation();
        $this->typeId = $id;
        $this->name = [];
        $this->hydrateTranslatable($type, ['name']);
        $this->slug = $type->slug;
        $this->autoSlug = '';
    }

    /** Back to a blank "new type" form. */
    public function resetForm(): void
    {
        $this->resetValidation();
        $this->reset(['typeId', 'name', 'slug', 'autoSlug']);
    }

    /**
     * Live slug-as-you-type — regenerates from the primary locale's name only
     * while the slug is still empty or still what we last generated (i.e. the
     * admin hasn't typed their own). An existing record's loaded slug never
     * matches autoSlug, so editing its name leaves the slug alone. Livewire's
     * updated{Field}() hooks don't fire for array keys like "name.en", hence
     * the catch-all.
     */
    public function updated(string $name, mixed $value): void
    {
        if (! $this->isPrimaryLocaleUpdate($name, 'name')) {
            return;
        }

        if ($this->slug === '' || $this->slug === $this->autoSlug) {
            $this->autoSlug = $this->slugFrom((string) $value);
            $this->slug = $this->autoSlug;
        }
    }

    public function updatedSlug(): void
    {
        $this->slug = Slug::lower($this->slug);
    }

    /**
     * The slug column holds 30 characters of lowercase kebab-case, so a long
     * name is cut there (without leaving a dangling hyphen).
     */
    private function slugFrom(string $name): string
    {
        return rtrim(Str::limit(Slug::make($name), 30, ''), '-');
    }

    public function save(): void
    {
        // The model derives a blank slug from the primary name on save, so
        // resolve it here first: `nullable` makes a blank field skip the unique
        // check (Laravel compares against NULL, which matches nothing), and the
        // derived value would then trip the unique index at INSERT time as a raw
        // 500 instead of a validation message. Guarded on having a name so an
        // empty form still fails on `name`, not on a confusing empty slug.
        if (blank($this->slug) && filled($this->primaryValue('name'))) {
            $this->slug = $this->slugFrom($this->primaryValue('name'));
        }

        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
        ]), [
            'slug' => ['nullable', 'string', 'max:30', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);

        $rules['name.'.$this->primaryLocale][] = Rule::unique('types', 'name->'.$this->primaryLocale)
            ->ignore($this->typeId);

        // Slugs are the stable key the rest of the system keys off (Type::idFor,
        // pages.type, the pool subqueries), so a rename must not collide.
        $rules['slug'][] = Rule::unique('types', 'slug')->ignore($this->typeId);

        $this->validate($rules);

        $data = [
            'name' => $this->translatablePayload('name'),
            'slug' => $this->slug ?: null,
        ];

        $creating = $this->typeId === null;

        if ($this->typeId) {
            // Status is toggled from the index list (see Index::toggleStatus()),
            // not this form — omit it here so saving never silently reactivates a
            // type an admin had pulled out of circulation.
            Type::findOrFail($this->typeId)->update($data);
            $this->dispatch('notify', message: 'Type updated successfully');
        } else {
            $data['status'] = 'active';
            $this->typeId = Type::create($data)->id;
            $this->dispatch('notify', message: 'Type created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Type: {$this->primaryValue('name')}",
        );

        // The list is right beside the form, so a created type just clears it
        // for the next one instead of navigating away.
        if ($creating) {
            $this->resetForm();
        }
    }

    public string $statusFilter = '';

    public ?int $deletingId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $type = Type::findOrFail($id);
        $newStatus = $type->status === 'active' ? 'inactive' : 'active';

        $type->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Type: {$type->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Type status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'type-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $type = Type::findOrFail($this->deletingId);

            if ($this->isInUse($type)) {
                $this->deletingId = null;
                $this->dispatch('notify', message: "{$type->name} is still assigned to categories, brands, tags or groups. Deactivate it instead.");
                $this->dispatch('close-modal', name: 'type-delete');

                return;
            }

            $deletedId = $type->id;
            AdminActivity::log('deleted', "Type: {$type->name}");
            $type->delete();
            $this->dispatch('notify', message: 'Type deleted successfully');
            $this->deletingId = null;

            // The type on screen is gone — fall back to a blank create form.
            if ($this->typeId === $deletedId) {
                $this->resetForm();
            }
        }
        $this->dispatch('close-modal', name: 'type-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'type-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $types = Type::whereIn('id', $this->selectedIds)->get();
        $count = 0;
        $skipped = 0;
        $deletedCurrent = false;

        foreach ($types as $type) {
            if ($this->isInUse($type)) {
                $skipped++;

                continue;
            }

            AdminActivity::log('deleted', "Type: {$type->name}");
            $deletedCurrent = $deletedCurrent || $type->id === $this->typeId;
            $type->delete();
            $count++;
        }

        $this->selectedIds = [];

        // A bulk run is a mixed bag far more often than a single delete, so say
        // what was left behind instead of letting it look like a full success.
        $message = "{$count} ".Str::plural('type', $count).' deleted successfully';

        if ($skipped > 0) {
            $message .= " — {$skipped} still in use, deactivate ".($skipped === 1 ? 'it' : 'them').' instead';
        }

        $this->dispatch('notify', message: $message);
        $this->dispatch('close-modal', name: 'type-bulk-delete');

        if ($deletedCurrent) {
            $this->resetForm();
        }
    }

    /**
     * Every category, brand, tag and group in that type would be left pointing
     * at a row nothing renders any more, so a type in use can only be
     * deactivated — that takes its rows out of circulation without orphaning
     * them.
     */
    private function isInUse(Type $type): bool
    {
        return $type->categories()->exists()
            || $type->brands()->exists()
            || $type->tags()->exists()
            || $type->groups()->exists();
    }

    public function render()
    {
        // The form is small, so the list sits beside it: pick a type to edit
        // without going back to the index.
        $types = Type::query()
            ->withCount(['categories', 'brands', 'tags', 'groups'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name->en', 'like', "%{$this->search}%")
                ->orWhere('name->bn', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($this->perPage);

        return view('livewire.admin.types.form', ['types' => $types])
            ->layout('layouts.admin', ['title' => $this->typeId ? 'Edit Type' : 'New Type']);
    }
}
