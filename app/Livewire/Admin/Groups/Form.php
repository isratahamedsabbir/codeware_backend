<?php

namespace App\Livewire\Admin\Groups;

use App\Concerns\HasTranslatableFields;
use App\Concerns\StaysOnForm;
use App\Models\Group;
use App\Models\Type;
use App\Support\AdminActivity;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;
    use StaysOnForm;

    public ?int $groupId = null;

    public array $name = [];

    public ?int $typeId = null;

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

        $group = Group::findOrFail($id);
        $this->groupId = $id;
        $this->typeId = $group->type_id;
        $this->hydrateTranslatable($group, ['name']);
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

        $this->stayOnForm($creating, 'admin.groups.edit', $this->groupId);
    }

    public function render()
    {
        return view('livewire.admin.groups.form', [
            'types' => Type::selectOptions(),
        ])->layout('layouts.admin', ['title' => $this->groupId ? 'Edit Group' : 'New Group']);
    }
}
