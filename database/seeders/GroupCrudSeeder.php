<?php

namespace Database\Seeders;

use App\Models\Crud;
use App\Models\Type;
use App\Support\Cruds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * The "Group" CRUD — a plain generated `crud_group` table (a `name` and a
 * `type` dropdown) that the Media Library uses to file gallery images into
 * groups. Built the same way the CRUD Builder screen builds any CRUD.
 *
 * Idempotent: an existing Group CRUD is left alone, except that a missing
 * field from the definition below is added to it (so installs that already
 * have the CRUD get the Type dropdown without losing their records).
 */
class GroupCrudSeeder extends Seeder
{
    public function run(): void
    {
        $definition = [
            ['label' => 'Name', 'type' => 'text', 'required' => true, 'in_list' => true, 'options' => ''],
            ['label' => 'Type', 'type' => 'select', 'required' => false, 'in_list' => true, 'options' => $this->typeOptions()],
        ];

        $crud = Crud::where('slug', Cruds::GROUP_SLUG)->first();

        if (! $crud) {
            $crud = new Crud([
                'name' => 'Group',
                'slug' => Cruds::GROUP_SLUG,
                'table_name' => Cruds::tableFor('Group'),
                'icon' => 'rectangle-stack',
                'fields' => Cruds::nameFields($definition),
            ]);

            if (! Schema::hasTable($crud->table_name)) {
                Cruds::createTable($crud);
            }

            $crud->save();

            return;
        }

        $existing = collect($crud->fields);
        $missing = collect($definition)
            ->reject(fn (array $d) => $existing->contains(fn (array $f) => $f['label'] === $d['label']))
            ->values()
            ->all();

        if ($missing === []) {
            return;
        }

        $missing = Cruds::nameFields($missing, $existing->pluck('name')->all());

        Cruds::addColumns($crud, $missing);
        $crud->update(['fields' => [...$crud->fields, ...$missing]]);
    }

    /** The content pools as a comma list, e.g. "Product,Post" — the select field's options. */
    private function typeOptions(): string
    {
        $names = Type::ordered()->get()
            ->map(fn (Type $type) => $type->getTranslation('name', 'en', false))
            ->filter()
            ->all();

        return implode(',', $names ?: ['Product', 'Post']);
    }
}
