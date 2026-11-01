<?php

namespace App\Services\Export;

use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PageCsvExporter extends CsvExporter
{
    /**
     * @param  list<int>  $ids
     * @return Collection<int, Page>
     */
    protected function records(array $ids, string $search, string $status, string $type = ''): Collection
    {
        return Page::query()
            ->when($ids !== [], fn ($q) => $q->whereIn('id', $ids), fn ($q) => $q
                ->when($search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('title->en', 'like', "%{$search}%")
                    ->orWhere('title->bn', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($type !== '' && $type !== 'all', fn ($q) => $q->where('type', $type)))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return list<string>
     */
    protected function headers(): array
    {
        return [
            'ID', 'Title (EN)', 'Title (BN)', 'Slug', 'Type', 'Template',
            'Status', 'Sort Order', 'Created At',
        ];
    }

    /**
     * @return list<int|string|null>
     */
    protected function row(Model $record): array
    {
        /** @var Page $record */
        return [
            $record->id,
            $record->getTranslation('title', 'en', false),
            $record->getTranslation('title', 'bn', false),
            $record->slug,
            $record->type,
            $record->template,
            $record->status,
            $record->sort_order,
            $record->created_at?->toDateTimeString(),
        ];
    }

    protected function fileBaseName(): string
    {
        return 'pages';
    }
}
