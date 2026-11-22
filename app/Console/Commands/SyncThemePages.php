<?php

namespace App\Console\Commands;

use App\Support\ThemePages;
use App\Support\Themes;
use Illuminate\Console\Command;

class SyncThemePages extends Command
{
    protected $signature = 'themes:sync-pages {theme? : One theme slug; every installed theme when omitted}';

    protected $description = 'Create the pages-table rows each theme lists in theme.json / ships in data/pages (never overwrites)';

    public function handle(): int
    {
        $themes = $this->argument('theme') ? [$this->argument('theme')] : array_keys(Themes::all());

        foreach ($themes as $slug) {
            if (! Themes::isInstalled($slug)) {
                $this->error("{$slug}: not installed");

                continue;
            }

            $r = ThemePages::sync($slug);
            $this->line(sprintf('%-20s created: %s | existing: %d | blocked: %s', $slug, implode(', ', $r['created']) ?: '-', count($r['existing']), implode(', ', $r['blocked']) ?: '-'));
        }

        return self::SUCCESS;
    }
}
