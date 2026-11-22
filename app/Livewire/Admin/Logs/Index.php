<?php

namespace App\Livewire\Admin\Logs;

use App\Support\AdminActivity;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Viewer for storage/logs/*.log. Only the newest TAIL_BYTES of the selected file
 * are parsed (a runaway log can be hundreds of MB); the full file is always
 * available through Download.
 */
class Index extends Component
{
    use WithPagination;

    private const TAIL_BYTES = 5 * 1024 * 1024;

    public string $file = '';

    public string $level = '';

    public string $search = '';

    public int $perPage = 25;

    public ?int $open = null;

    public function mount(): void
    {
        $this->file = $this->files()[0]['name'] ?? '';
    }

    /** @return array<int, array{name: string, size: int, modified: int}> newest first */
    public function files(): array
    {
        $files = [];

        foreach (glob(storage_path('logs/*.log')) ?: [] as $path) {
            $files[] = ['name' => basename($path), 'size' => (int) filesize($path), 'modified' => (int) filemtime($path)];
        }

        usort($files, fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return $files;
    }

    /** Resolve the picked name against the real listing, so no path ever comes from the wire. */
    private function path(): ?string
    {
        foreach ($this->files() as $f) {
            if ($f['name'] === $this->file) {
                return storage_path('logs/'.$f['name']);
            }
        }

        return null;
    }

    public function updated(string $name): void
    {
        $this->open = null;
        $this->resetPage();
    }

    public function toggle(int $index): void
    {
        $this->open = $this->open === $index ? null : $index;
    }

    public function download(): ?BinaryFileResponse
    {
        $path = $this->path();

        return $path ? response()->download($path) : null;
    }

    public function clear(): void
    {
        if ($path = $this->path()) {
            file_put_contents($path, '');
            AdminActivity::log('logs.clear', "Log file {$this->file} cleared");
        }

        $this->open = null;
        $this->resetPage();
    }

    public function delete(): void
    {
        if ($path = $this->path()) {
            @unlink($path);
            AdminActivity::log('logs.delete', "Log file {$this->file} deleted");
        }

        $this->file = $this->files()[0]['name'] ?? '';
        $this->open = null;
        $this->resetPage();
    }

    /** @return array{entries: array<int, array<string, string>>, truncated: bool} newest first */
    private function parse(string $path): array
    {
        $size = (int) filesize($path);
        $truncated = $size > self::TAIL_BYTES;

        $h = fopen($path, 'rb');
        if ($truncated) {
            fseek($h, -self::TAIL_BYTES, SEEK_END);
            fgets($h); // drop the partial first line
        }
        $raw = stream_get_contents($h);
        fclose($h);

        $parts = preg_split('/^(?=\[\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})/m', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
        $entries = [];

        foreach ($parts as $part) {
            if (! preg_match('/^\[([^\]]+)\] (\w+)\.(\w+): (.*)$/s', $part, $m)) {
                continue;
            }
            [$first, $rest] = array_pad(explode("\n", $m[4], 2), 2, '');
            $entries[] = [
                'time' => $m[1],
                'env' => $m[2],
                'level' => strtolower($m[3]),
                'message' => $first,
                'detail' => trim($rest),
            ];
        }

        return ['entries' => array_reverse($entries), 'truncated' => $truncated];
    }

    public function render()
    {
        $path = $this->path();
        $parsed = $path ? $this->parse($path) : ['entries' => [], 'truncated' => false];

        $levels = array_values(array_unique(array_column($parsed['entries'], 'level')));
        sort($levels);

        $entries = array_values(array_filter($parsed['entries'], function ($e) {
            if ($this->level !== '' && $e['level'] !== $this->level) {
                return false;
            }

            return $this->search === ''
                || stripos($e['message'].' '.$e['detail'], $this->search) !== false;
        }));

        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 25;
        $page = $this->getPage();
        $paginator = new LengthAwarePaginator(
            array_slice($entries, ($page - 1) * $perPage, $perPage),
            count($entries),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );

        return view('livewire.admin.logs.index', [
            'files' => $this->files(),
            'entries' => $paginator,
            'levels' => $levels,
            'truncated' => $parsed['truncated'],
            'offset' => ($page - 1) * $perPage,
        ])->layout('layouts.admin', ['title' => 'Laravel Logs']);
    }
}
