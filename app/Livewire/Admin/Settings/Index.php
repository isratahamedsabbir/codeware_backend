<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\CustomCode;
use App\Support\EnvFile;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class Index extends Component
{
    public array $settings = [];

    public string $activeTab = 'general';

    /**
     * APP_ENV — lives in the .env file, not the Setting table, so it's saved
     * through EnvFile::set() (see saveEnvironment()) rather than the generic
     * save() below. Moved here from the Developer Tools page so it sits next
     * to the rest of the app's identity settings.
     */
    public string $appEnv = '';

    public function mount(): void
    {
        $this->loadSettings();
        $this->appEnv = EnvFile::get('APP_ENV', config('app.env')) ?? config('app.env');
    }

    protected function loadSettings(): void
    {
        $rows = Setting::all();

        foreach ($rows as $setting) {
            // Watermark is configured from the Media Library now, not here — it
            // must not be re-written by this page's save(), so leave it out.
            if (str_starts_with($setting->key, 'watermark_')) {
                continue;
            }

            // Currency/VAT, colors, localization, pagination and newsletter are edited on
            // Developer Tools → Site Settings (App\Livewire\Admin\Env\Index), which writes
            // them back; left here this page would overwrite them with its own copy.
            if (in_array($setting->group, \App\Livewire\Admin\Env\Index::SITE_GROUPS, true) || $setting->key === 'admin_font') {
                continue;
            }

            // URL prefixes are edited from the Products / Posts / Services list
            // screens; left in $settings they would be written back by save().
            if (str_starts_with($setting->key, 'url_prefix_')) {
                continue;
            }

            // The page-specific custom code rules are edited as $codeRules, and
            // saved from there — left in $settings, the stale JSON string would
            // be written back over them by persistGeneralSettings().
            if ($setting->key === CustomCode::SETTING || in_array($setting->key, ['custom_head_code', 'custom_body_code'], true)) {
                continue;
            }

            // Boolean settings are stored as the string "0"/"1" (no cast on the
            // Setting model). Left as a string, a checkbox bound to it renders
            // checked no matter what — JS truthiness treats "0" as true, unlike
            // PHP. Cast to a real boolean so the checkbox reflects the stored value.
            $this->settings[$setting->key] = $setting->type === 'boolean'
                ? (bool) $setting->value
                : ($setting->value ?? '');
        }

        // The row is seeded, but a database that predates the setting has none —
        // and the select would then render with nothing selected, which reads
        // as "unset" rather than as the deliberate default. Default it to the
        // system font, the same value the seeder writes. A saved font whose
        // folder has since been deleted is shown as the system font too, which
        // is what the panel is already rendering in.
        // Same for the header app-name label: a database that predates it has no
        // row, and the header treats "no row" as on, so the checkbox must too.
        // Cast explicitly: a row first written by save() (not the seeder) has
        // type "string", so the loop above would leave "0" as a truthy string.
        $this->settings['app_name_label_enabled'] = (bool) ($this->settings['app_name_label_enabled'] ?? true);

    }

    public function save(): void
    {
        $this->persistGeneralSettings();

        // A real browser reload of the current page, rather than a dispatched
        // toast, so the whole admin shell re-renders on a fresh request —
        // header icons, colors, and anything else read from Setting::get()
        // in layouts.admin only reflect a change after that, since Livewire's
        // own re-render never touches the surrounding layout. (Redirecting to
        // request()->fullUrl() would resolve to the Livewire update endpoint
        // itself, not the page — hence a plain client-side reload instead.)
        session()->flash('success', 'Settings saved.');
        $this->js('window.location.reload()');
    }

    public function confirmSaveEnvironment(): void
    {
        $this->validate([
            'appEnv' => 'required|in:local,staging,production,testing,developer',
        ]);

        $this->dispatch('open-modal', name: 'settings-env-confirm');
    }

    /**
     * Writes APP_ENV straight to the .env file, same mechanism (and risk) as
     * the rest of the Developer Tools page — see App\Livewire\Admin\Env\Index.
     */
    public function saveEnvironment(): void
    {
        try {
            EnvFile::set(['APP_ENV' => $this->appEnv]);
        } catch (\RuntimeException $e) {
            $this->dispatch('close-modal', name: 'settings-env-confirm');
            $this->dispatch('notify', message: 'Could not update the environment: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        AdminActivity::log('updated', 'Environment updated');

        $this->dispatch('close-modal', name: 'settings-env-confirm');
        session()->flash('success', 'Environment updated. Configuration cache cleared.');
        $this->js('window.location.reload()');
    }

    /**
     * Persists every plain (DB-backed) Setting row. The .env-backed fields on
     * the standalone Env page (see App\Livewire\Admin\Env\Index) go through
     * EnvFile::set() instead, not this.
     */
    private function persistGeneralSettings(): void
    {
        foreach ($this->settings as $key => $value) {
            // The panel typeface is stored as a theme slug, so it is validated
            // against the options that exist now: a value whose theme is gone
            // (uninstalled, or hand-edited) becomes the system default rather
            // than a dangling reference that resolves to nothing.
            Setting::set($key, $value);
        }
    }

    public function render()
    {
        // Section order within the General tab — not the DB row order, which isn't
        // guaranteed without an ORDER BY. General and Images render side by side (see
        // the view), so their relative order here doesn't matter; anything not listed
        // falls to the end in whatever order it comes. Pagination gets its own card
        // (via its own 'pagination' group) rather than sharing General's.
        $groupOrder = ['general' => 0, 'pagination' => 1, 'images' => 2, 'localization' => 3, 'newsletter' => 4];

        return view('livewire.admin.settings.index', [
            // 'colors' renders hand-rolled as the Backend card in the General tab,
            // not through this generic per-group loop. 'frontend' (site_theme and the
            // theme homepage copy/imagery) lives on the dedicated Theme Settings screen.
            // 'currency' (currency + VAT fields with a live-preview modal) is hand-rendered
            // in the General tab, not through this generic per-group loop. 'other' is
            // hand-rendered in its own tab (the Floating Button card) rather than through
            // this generic per-group loop. 'shop' (shop_enabled) is controlled only via the
            // header toggle (ShopToggle), never a form field here.
            // 'orders' (order_cancellation_cutoff_status) has its own settings modal on
            // the admin Orders screen, same as 'editor' does on Pages — though the
            // editor's own token expiry now lives in .env (PUCK_SESSION) and has no
            // settings row at all; 'editor' is still excluded so a row left over from
            // an older database never surfaces here.
            // 'admin' (admin_font) is hand-rendered in the Backend card above, not
            // through this generic per-group loop — it needs a select, not a plain
            // text input. 'security' is excluded only as a backstop: MFA and
            // reCAPTCHA enforcement moved to the per-role switches on Roles, and
            // the migration deleted those rows, so nothing seeds that group any
            // more — but a row left behind on an older database would otherwise
            // render here as a bare checkbox with no explanation.
            'groupedSettings' => Setting::whereNotIn('group', ['layout', 'seo', 'colors', 'currency', 'frontend', 'other', 'editor', 'custom-code', 'localization', 'pagination', 'newsletter', 'tracking', 'shop', 'orders', 'admin', 'security', 'urls'])
                ->get()
                ->groupBy('group')
                ->sortBy(fn ($items, $group) => $groupOrder[$group] ?? count($groupOrder)),
        ])->layout('layouts.admin', ['title' => 'Settings']);
    }
}
