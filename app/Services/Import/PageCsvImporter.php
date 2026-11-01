<?php

namespace App\Services\Import;

use App\Models\Page;
use App\Support\Slug;
use RuntimeException;

/**
 * Turns the Pages screen's CSV back into pages.
 *
 * Columns, in the order the export writes them:
 *
 *   ID, Title (EN), Title (BN), Slug, Type, Template, Status, Sort Order,
 *   Created At
 *
 * Only Title (EN) is required for a new row. Existing pages are found by ID,
 * then slug, and are only overwritten when the "update existing" toggle is on
 * — see the import modal on the Pages screen.
 *
 * Only standalone pages (type "page") can be created from a sheet. The
 * companion pages behind products, posts and categories are owned by their own
 * screens, so a row naming one of those types is reported as a row error
 * instead of creating a page with no entity behind it.
 */
class PageCsvImporter extends CsvImporter
{
    protected function requiredColumn(): string
    {
        return 'title en';
    }

    protected function requiredColumnLabel(): string
    {
        return 'Title (EN)';
    }

    /**
     * @param  array<string, string>  $row
     * @return array{status: 'created'|'updated'|'skipped', warnings?: list<string>}
     */
    protected function importRow(array $row, bool $updateExisting, int $userId): array
    {
        $titleEn = $this->value($row, 'title en');

        if ($titleEn === '') {
            throw new RuntimeException('Title (EN) is required.');
        }

        $existing = $this->findExisting($row);

        if ($existing && ! $updateExisting) {
            return ['status' => 'skipped'];
        }

        $creating = ! $existing;
        $warnings = [];
        $type = strtolower($this->value($row, 'type'));

        if ($creating && $type !== '' && $type !== 'page') {
            throw new RuntimeException(
                'Only standalone pages (Type "page") can be imported; "'.$type.'" pages are created from their own screens.'
            );
        }

        if ($existing && $type !== '' && $type !== $existing->type) {
            $warnings[] = 'Type "'.$type.'" was ignored; this page stays a "'.$existing->type.'" page.';
        }

        $data = [
            'title' => $this->translations($row, 'title en', 'title bn') ?: ['en' => $titleEn],
        ];

        if ($this->has($row, 'template') && ! $this->empty($row, 'template')) {
            $data['template'] = $this->value($row, 'template');
        } elseif ($creating) {
            $data['template'] = 'puck';
        }

        if (($status = $this->status($row, $creating)) !== null) {
            $data['status'] = $status;
        }

        if ($this->has($row, 'sort order') && ! $this->empty($row, 'sort order')) {
            $data['sort_order'] = $this->integer($row, 'sort order');
        } elseif ($creating) {
            $data['sort_order'] = ((int) Page::max('sort_order')) + 1;
        }

        // A linked page's slug belongs to the entity it fronts, so an import
        // never rewrites it — only standalone pages take the sheet's value.
        if ($creating || $existing->type === 'page') {
            $data['slug'] = $this->uniqueSlug(
                $this->desiredSlug($row, $existing, $titleEn),
                $existing?->id,
                'page',
            );
        }

        if ($creating) {
            $data['user_id'] = $userId;
            $data['type'] = 'page';
        }

        if ($existing) {
            $existing->update($data);

            return ['status' => 'updated', 'warnings' => $warnings];
        }

        Page::create($data);

        return ['status' => 'created'];
    }

    /**
     * Finds the row's existing page by ID first, then by slug.
     *
     * @param  array<string, string>  $row
     */
    protected function findExisting(array $row): ?Page
    {
        $id = (int) $this->value($row, 'id');

        if ($id > 0 && ($page = Page::find($id))) {
            return $page;
        }

        $slug = $this->value($row, 'slug');

        if ($slug !== '') {
            return Page::query()->where('slug', Slug::lower($slug))->first();
        }

        return null;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function desiredSlug(array $row, ?Page $existing, string $titleEn): string
    {
        $slug = $this->value($row, 'slug');

        if ($slug !== '') {
            return $slug;
        }

        return $existing?->slug ?? Slug::make($titleEn);
    }

    protected function integer(array $row, string $key, int $default = 0): int
    {
        $value = $this->value($row, $key);

        if ($value === '') {
            return $default;
        }

        if (! is_numeric($value)) {
            throw new RuntimeException('"'.$key.'" must be a whole number: '.$value);
        }

        return max(0, (int) $value);
    }
}
