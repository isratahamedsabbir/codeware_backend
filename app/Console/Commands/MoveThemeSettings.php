<?php

namespace App\Console\Commands;

use App\Support\ThemeSettings;
use App\Support\Themes;
use Illuminate\Console\Command;

/**
 * One-off: copy the values currently in each theme's theme.json into
 * storage/themes/{slug}.json, where the admin screen now saves them.
 *
 * Run it on a server BEFORE the first deploy that replaces theme.json, so the
 * settings the owner configured there survive the deploy. Safe to repeat: a theme
 * that already has saved values is left alone.
 */
class MoveThemeSettings extends Command
{
    protected $signature = 'themes:move-settings';

    protected $description = 'Copy each theme.json\'s settings into storage/themes (where admin saves now)';

    public function handle(): int
    {
        foreach (array_keys(Themes::all()) as $slug) {
            $this->line(sprintf('%-20s %s', $slug, ThemeSettings::moveToSaved($slug) ? 'moved' : 'nothing to move'));
        }

        return self::SUCCESS;
    }
}
