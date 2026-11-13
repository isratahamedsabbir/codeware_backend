<?php

namespace App\Livewire\Admin\ThemeSettings;

use App\Support\Themes;

/**
 * One theme's settings on a page of their own: /theme-settings/{slug}.
 *
 * The theme list (Index) is where a theme is picked, installed, downloaded and
 * deleted. Its Settings button lands here, where that theme's Theme Settings
 * (the global look) and Page Settings (its pages and their sections) are edited.
 * Same state, same save as Index — this only changes which theme is edited and
 * which view is drawn — and it never changes which theme is live.
 */
class Edit extends Index
{
    public function mount(?string $slug = null): void
    {
        abort_unless($slug !== null && Themes::isInstalled($slug), 404);

        $this->settingsFor = $slug;

        parent::mount();
    }

    public function render()
    {
        return view('livewire.admin.theme-settings.edit', $this->viewData())
            ->layout('layouts.admin', ['title' => Themes::manifest((string) $this->settingsFor)['name'].' · Settings']);
    }
}
