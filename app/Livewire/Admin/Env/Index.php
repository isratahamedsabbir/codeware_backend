<?php

namespace App\Livewire\Admin\Env;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\AdminFont;
use App\Support\CustomCode;
use App\Support\EnvFile;
use App\Support\PuckEditor;
use App\Support\Telegram;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Index extends Component
{
    /** Settings groups edited on the Site Settings tab. */
    public const SITE_GROUPS = ['currency', 'colors', 'localization', 'pagination', 'newsletter'];

    public array $env = [];

    public bool $maintenanceMode = false;

    public bool $debugMode = false;

    /** Global head/body code (custom_head_code / custom_body_code) — moved here from Settings. */
    public array $settings = [];

    /** @var array<int, array{key: string, type: string, value: string}> */
    public array $constants = [];

    /** @var array<int, int> Expanded $constants rows, tracked server-side so re-renders don't collapse them. */
    public array $openConstants = [];

    /** @var array<int, array{id: string, name: string, head: string, body: string}> */
    public array $codeRules = [];

    /** @var array<int, string> */
    public array $openCodeRules = [];

    /**
     * Telegram bot token and chat id — TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID in
     * .env, like APP_ENV above, so they are saved through EnvFile::set() rather
     * than the settings table (whose rows the public API can serve).
     */
    public string $telegramBotToken = '';

    public string $telegramChatId = '';

    public function mount(): void
    {
        $this->moveLegacyTelegramRows();
        $this->telegramBotToken = (string) (EnvFile::get('TELEGRAM_BOT_TOKEN') ?? '');
        $this->telegramChatId = (string) (EnvFile::get('TELEGRAM_CHAT_ID') ?? '');

        $this->loadSiteSettings();
        $this->codeRules = CustomCode::rules();
        $this->loadConstants();
        $this->loadEnv();
        $this->maintenanceMode = app()->isDownForMaintenance();
        $this->debugMode = (bool) config('app.debug');
    }

    protected function loadEnv(): void
    {
        $current = EnvFile::all();

        foreach ($this->envFields() as $fields) {
            foreach (array_keys($fields) as $key) {
                $this->env[$key] = $current[$key] ?? '';
            }
        }
    }

    /**
     * Editable .env keys, grouped for this page. Deliberately excludes APP_KEY and
     * anything else whose value would be unsafe to expose or that shouldn't be edited
     * through a web form (encryption key, session driver, etc.). CACHE_STORE is the one
     * exception — it's exposed further down so a server without Redis can be switched
     * to 'database'/'file' without touching the file by hand (see saveEnv()'s Redis
     * reachability check). Mail credentials live on the Email Templates page instead
     * (see EmailTemplates\Index), next to the "send a test email" action that actually
     * exercises them.
     *
     * @return array<string, array<string, array{label: string, type: string, options?: array<int, string>, hint?: string}>>
     */
    public function envFields(): array
    {
        return [
            'App' => [
                'APP_NAME' => ['label' => 'App Name', 'type' => 'text'],
                // Environment lives on Settings > General now (App\Livewire\Admin\Settings\Index),
                // not here — it gets its own save action and confirm modal there.
                'APP_URL' => ['label' => 'App URL', 'type' => 'text'],
                'FRONTEND_URL' => ['label' => 'Frontend URL', 'type' => 'text'],
                // Vendor Portal subdomain (see bootstrap/app.php) — e.g.
                // https://vendor.codeware.test locally, https://vendor.codeware.com
                // in production. Requires the corresponding DNS/hosts entry to
                // already exist; this only changes which host Laravel routes to
                // App\Livewire\Vendor\* and where vendor.* URLs point.
                'VENDOR_URL' => ['label' => 'Vendor Portal URL', 'type' => 'text'],
                // Delivery Portal subdomain — same idea as VENDOR_URL, for
                // App\Livewire\Delivery\* (e.g. https://deliveryboy.codeware.test).
                'DELIVERY_URL' => ['label' => 'Delivery Portal URL', 'type' => 'text'],
                // 'database'/'file' work on any server with no extra setup; 'redis' is
                // faster but only picked when the server actually has one — saveEnv()
                // refuses to save 'redis' here unless it can reach it first.
                'CACHE_STORE' => ['label' => 'Cache Store', 'type' => 'select', 'options' => ['database', 'file', 'redis'],
                    'hint' => "Pick database or file if this server doesn't have Redis installed — redis is only saved once it's confirmed reachable."],
            ],
            'Google Login' => [
                'GOOGLE_CLIENT_ID' => ['label' => 'Google Client ID', 'type' => 'text'],
                'GOOGLE_CLIENT_SECRET' => ['label' => 'Google Client Secret', 'type' => 'password'],
                'GOOGLE_REDIRECT_URI' => ['label' => 'Google Redirect URI', 'type' => 'text'],
            ],
            'CMS Editor' => [
                'CMS_EDITOR_BASE_URL' => ['label' => 'CMS Editor Base URL', 'type' => 'text',
                    'hint' => 'Base URL of the Next.js Puck editor that loads when you open a post, page or product in the visual editor (e.g. https://editor.example.com).'],
            ],
            'Facebook Login' => [
                'FACEBOOK_CLIENT_ID' => ['label' => 'Facebook App ID', 'type' => 'text'],
                'FACEBOOK_CLIENT_SECRET' => ['label' => 'Facebook App Secret', 'type' => 'password'],
                'FACEBOOK_REDIRECT_URI' => ['label' => 'Facebook Redirect URI', 'type' => 'text'],
            ],
            'Captcha' => [
                'CAPTCHA_PROVIDER' => ['label' => 'Active Captcha', 'type' => 'select', 'options' => ['recaptcha', 'turnstile'],
                    'hint' => 'Which service the login forms use. Keys for both can stay saved; only this one is asked for and verified.'],
            ],
            'reCAPTCHA' => [
                'RECAPTCHA_SITE_KEY' => ['label' => 'Site Key', 'type' => 'text'],
                'RECAPTCHA_SECRET_KEY' => ['label' => 'Secret Key', 'type' => 'password'],
            ],
            'Turnstile' => [
                'TURNSTILE_SITE_KEY' => ['label' => 'Site Key', 'type' => 'text'],
                'TURNSTILE_SECRET_KEY' => ['label' => 'Secret Key', 'type' => 'password'],
            ],
            'Google Maps' => [
                'GOOGLE_MAPS_API_KEY' => ['label' => 'API Key', 'type' => 'text'],
            ],
            'Google Tag Manager' => [
                'GOOGLE_TAG_MANAGER_ID' => ['label' => 'Container ID', 'type' => 'text',
                    'hint' => 'e.g. GTM-XXXXXXX. Loaded on every public theme page; leave it blank to turn the container off.'],
            ],
            'AWS S3' => [
                'AWS_ACCESS_KEY_ID' => ['label' => 'Access Key ID', 'type' => 'text'],
                'AWS_SECRET_ACCESS_KEY' => ['label' => 'Secret Access Key', 'type' => 'password'],
                'AWS_DEFAULT_REGION' => ['label' => 'Region', 'type' => 'text', 'hint' => 'e.g. us-east-1, ap-southeast-1.'],
                'AWS_BUCKET' => ['label' => 'Bucket', 'type' => 'text'],
                'AWS_USE_PATH_STYLE_ENDPOINT' => ['label' => 'Use Path-Style Endpoint', 'type' => 'boolean',
                    'hint' => 'Turn on only for S3-compatible services (e.g. MinIO, DigitalOcean Spaces) that need it — leave off for real AWS S3.'],
            ],
            'Firebase' => [
                'FIREBASE_CREDENTIALS_PATH' => ['label' => 'Service Account JSON Path', 'type' => 'text',
                    'hint' => 'Path relative to storage/app/private — upload the file there via File Manager first, then paste its path here.'],
            ],
        ];
    }

    public function confirmSaveEnv(): void
    {
        $rules = [
            'env.APP_NAME' => 'required|string',
            'env.APP_URL' => 'required|url',
            'env.FRONTEND_URL' => 'nullable|url',
            'env.VENDOR_URL' => 'nullable|url',
            'env.DELIVERY_URL' => 'nullable|url',
            'env.CACHE_STORE' => 'required|in:database,file,redis',
            'env.GOOGLE_CLIENT_ID' => 'nullable|string',
            'env.GOOGLE_CLIENT_SECRET' => 'nullable|string',
            // Not `url` — this intentionally holds a ${APP_URL}/... interpolation
            // (phpdotenv resolves it at runtime), which a strict URL check would reject.
            'env.GOOGLE_REDIRECT_URI' => 'nullable|string',
            'env.CMS_EDITOR_BASE_URL' => ['nullable', 'url', PuckEditor::baseUrlRule()],
            'env.FACEBOOK_CLIENT_ID' => 'nullable|string',
            'env.FACEBOOK_CLIENT_SECRET' => 'nullable|string',
            'env.FACEBOOK_REDIRECT_URI' => 'nullable|string',
            'env.CAPTCHA_PROVIDER' => 'nullable|in:recaptcha,turnstile',
            'env.RECAPTCHA_SITE_KEY' => 'nullable|string',
            'env.RECAPTCHA_SECRET_KEY' => 'nullable|string',
            'env.TURNSTILE_SITE_KEY' => 'nullable|string',
            'env.TURNSTILE_SECRET_KEY' => 'nullable|string',
            'env.GOOGLE_MAPS_API_KEY' => 'nullable|string',
            'env.GOOGLE_TAG_MANAGER_ID' => ['nullable', 'string', 'regex:/^(GTM-[A-Za-z0-9]+)?$/'],
            'env.AWS_ACCESS_KEY_ID' => 'nullable|string',
            'env.AWS_SECRET_ACCESS_KEY' => 'nullable|string',
            'env.AWS_DEFAULT_REGION' => 'nullable|string',
            'env.AWS_BUCKET' => 'nullable|string',
            'env.AWS_USE_PATH_STYLE_ENDPOINT' => 'nullable|in:true,false',
            'env.FIREBASE_CREDENTIALS_PATH' => 'nullable|string',
        ];

        // Every field, whether it has a rule above or not, must be a single line:
        // a newline would start a new .env line and could set another key.
        foreach (array_keys($this->env) as $key) {
            $existing = $rules["env.{$key}"] ?? ['nullable', 'string'];
            $rules["env.{$key}"] = [...(is_array($existing) ? $existing : explode('|', $existing)), 'regex:/^[^\r\n\0]*$/D'];
        }

        $this->validate($rules);

        $this->dispatch('open-modal', name: 'env-save-confirm');
    }

    /**
     * Refuses to switch the cache store to Redis unless it can actually be
     * reached first — the whole point of exposing CACHE_STORE here is to let
     * a server without Redis fall back to 'database'/'file', so silently
     * saving 'redis' anyway would take every cache read/write down site-wide
     * the moment config:clear picks it up.
     */
    private function redisReachable(): bool
    {
        try {
            Redis::connection('cache')->ping();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Whether the currently-typed Firebase credentials path actually exists
     * on the private storage disk — checked live (see the field's
     * wire:model.live) so the warning in the UI reflects what's typed, not
     * just what was last saved.
     */
    public function firebaseCredentialsExist(): bool
    {
        $path = trim($this->env['FIREBASE_CREDENTIALS_PATH'] ?? '');

        return $path !== '' && Storage::disk('local')->exists($path);
    }

    public function saveEnv(): void
    {
        if (($this->env['CACHE_STORE'] ?? null) === 'redis' && ! $this->redisReachable()) {
            $this->dispatch('close-modal', name: 'env-save-confirm');
            $this->dispatch('notify', message: 'Could not save: Redis is not reachable from this server. Choose "database" or "file" instead, or fix the Redis connection first.');

            return;
        }

        try {
            EnvFile::set($this->env);
        } catch (\RuntimeException $e) {
            $this->dispatch('close-modal', name: 'env-save-confirm');
            $this->dispatch('notify', message: 'Could not save environment settings: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        // VENDOR_URL/DELIVERY_URL control which host each portal's route group binds to
        // (see bootstrap/app.php) — if routes are ever cached (route:cache, as a
        // production deploy might run), that cache would keep serving the old
        // host until cleared here too.
        Artisan::call('route:clear');

        AdminActivity::log('updated', 'Environment settings updated');

        $this->dispatch('close-modal', name: 'env-save-confirm');
        session()->flash('success', 'Environment settings saved. Configuration cache cleared.');
        $this->js('window.location.reload()');
    }

    public function confirmEnableMaintenanceMode(): void
    {
        $this->dispatch('open-modal', name: 'maintenance-mode-confirm');
    }

    /**
     * The header <flux:switch> binds straight to maintenanceMode, so by now
     * the property already holds the requested value — put the real state
     * back and run the toggle, which only commits it once the change sticks.
     */
    public function updatedMaintenanceMode(bool $value): void
    {
        $this->maintenanceMode = ! $value;
        $this->toggleMaintenanceMode();
    }

    /** Same as updatedMaintenanceMode(), for the Debug Mode header switch. */
    public function updatedDebugMode(bool $value): void
    {
        $this->debugMode = ! $value;
        $this->toggleDebugMode();
    }

    /**
     * Computes the target from the CURRENT state, flips the real maintenance
     * state, and reverts if the artisan command didn't stick (e.g. it
     * couldn't write the down file).
     */
    public function toggleMaintenanceMode(): void
    {
        $target = ! $this->maintenanceMode;

        Artisan::call($target ? 'down' : 'up');

        if (app()->isDownForMaintenance() !== $target) {
            $this->dispatch('notify', message: 'Could not change maintenance mode.');

            return;
        }

        $this->maintenanceMode = $target;

        AdminActivity::log('updated', $target ? 'Enabled maintenance mode' : 'Disabled maintenance mode');

        $this->dispatch('notify', message: $target
            ? 'Maintenance mode enabled. The public site is now offline.'
            : 'Maintenance mode disabled. The site is back online.');
    }

    /**
     * Takes the public site offline — the admin panel and /login stay reachable
     * regardless (see bootstrap/app.php's preventRequestsDuringMaintenance
     * exceptions), so this can never lock the admin out of turning it back off.
     */
    public function enableMaintenanceMode(): void
    {
        Artisan::call('down');

        $this->maintenanceMode = true;

        AdminActivity::log('updated', 'Enabled maintenance mode');

        $this->dispatch('close-modal', name: 'maintenance-mode-confirm');
        $this->dispatch('notify', message: 'Maintenance mode enabled. The public site is now offline.');
    }

    public function disableMaintenanceMode(): void
    {
        Artisan::call('up');

        $this->maintenanceMode = false;

        AdminActivity::log('updated', 'Disabled maintenance mode');

        $this->dispatch('notify', message: 'Maintenance mode disabled. The site is back online.');
    }

    /**
     * Turning APP_DEBUG on in production leaks stack traces, config and secrets to
     * visitors, so it is refused there (turning it off always works). Set
     * ALLOW_DEBUG_IN_PRODUCTION=true to lift the lock.
     */
    public function debugLocked(): bool
    {
        return ! app()->environment(['local', 'testing']) && ! config('security.allow_debug_in_production');
    }

    public function confirmEnableDebugMode(): void
    {
        if ($this->debugLocked()) {
            $this->dispatch('notify', message: 'Debug mode cannot be enabled in production.');

            return;
        }

        $this->dispatch('open-modal', name: 'debug-mode-confirm');
    }

    /**
     * Computes the target from the CURRENT state, writes APP_DEBUG to match,
     * and reverts with an error if the write can't happen.
     */
    public function toggleDebugMode(): void
    {
        $target = ! $this->debugMode;

        if ($target && $this->debugLocked()) {
            $this->debugMode = false;
            $this->dispatch('notify', message: 'Debug mode cannot be enabled in production.');

            return;
        }

        try {
            EnvFile::set(['APP_DEBUG' => $target ? 'true' : 'false']);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', message: 'Could not update debug mode: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        $this->debugMode = $target;

        AdminActivity::log('updated', $target ? 'Enabled debug mode' : 'Disabled debug mode');

        $this->dispatch('notify', message: $target
            ? 'Debug mode enabled. Errors will now show full stack traces to visitors.'
            : 'Debug mode disabled.');
    }

    /**
     * Debug mode has no artisan down/up equivalent — it's just APP_DEBUG in .env,
     * read through config('app.debug'). Writing it goes through EnvFile (same as
     * saveEnv()) followed by config:clear so the change takes effect immediately.
     */
    public function enableDebugMode(): void
    {
        if ($this->debugLocked()) {
            $this->dispatch('notify', message: 'Debug mode cannot be enabled in production.');

            return;
        }

        if (! $this->writeDebugMode('true')) {
            return;
        }

        $this->debugMode = true;

        AdminActivity::log('updated', 'Enabled debug mode');

        $this->dispatch('close-modal', name: 'debug-mode-confirm');
        $this->dispatch('notify', message: 'Debug mode enabled. Errors will now show full stack traces to visitors.');
    }

    public function disableDebugMode(): void
    {
        if (! $this->writeDebugMode('false')) {
            return;
        }

        $this->debugMode = false;

        AdminActivity::log('updated', 'Disabled debug mode');

        $this->dispatch('notify', message: 'Debug mode disabled.');
    }

    protected function writeDebugMode(string $value): bool
    {
        try {
            EnvFile::set(['APP_DEBUG' => $value]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', message: 'Could not update debug mode: '.$e->getMessage());

            return false;
        }

        Artisan::call('config:clear');

        return true;
    }

    /** Currency/VAT, colors, localization, pagination, newsletter, admin font and the global custom code pair. */
    protected function loadSiteSettings(): void
    {
        $rows = Setting::whereIn('group', self::SITE_GROUPS)
            ->orWhereIn('key', ['admin_font', 'custom_head_code', 'custom_body_code'])
            ->get();

        foreach ($rows as $setting) {
            // Boolean settings are stored as "0"/"1"; a checkbox bound to the string
            // would always render checked, so cast to a real boolean.
            $this->settings[$setting->key] = $setting->type === 'boolean'
                ? (bool) $setting->value
                : ($setting->value ?? '');
        }

        $this->settings['custom_head_code'] = (string) ($this->settings['custom_head_code'] ?? '');
        $this->settings['custom_body_code'] = (string) ($this->settings['custom_body_code'] ?? '');
        $this->settings['admin_font'] = AdminFont::normalize($this->settings['admin_font'] ?? AdminFont::SYSTEM);
    }

    /**
     * The token and chat id were briefly stored as settings rows. Carry any such
     * value over to .env (unless .env already has one) and drop the rows, so a
     * secret is not left in the table — and so the rows do not turn up as plain
     * text fields in the generic settings list.
     */
    private function moveLegacyTelegramRows(): void
    {
        $rows = Setting::whereIn('key', ['telegram_bot_token', 'telegram_chat_id'])->pluck('value', 'key');

        if ($rows->isEmpty()) {
            return;
        }

        $carry = [];

        foreach (['telegram_bot_token' => 'TELEGRAM_BOT_TOKEN', 'telegram_chat_id' => 'TELEGRAM_CHAT_ID'] as $key => $envKey) {
            if (filled($rows[$key] ?? null) && blank(EnvFile::get($envKey))) {
                $carry[$envKey] = (string) $rows[$key];
            }
        }

        try {
            if ($carry !== []) {
                EnvFile::set($carry);
                Artisan::call('config:clear');
            }
        } catch (\RuntimeException) {
            // .env is not writable: keep the rows rather than lose the values.
            return;
        }

        Setting::forget('telegram_bot_token');
        Setting::forget('telegram_chat_id');
    }

    /**
     * Writes the Telegram card to .env. EnvFile only rewrites a key whose value
     * changed, and the config cache is cleared only then, so saving the page
     * with the card untouched does neither.
     */
    private function persistTelegram(): bool
    {
        $values = [
            'TELEGRAM_BOT_TOKEN' => trim($this->telegramBotToken),
            'TELEGRAM_CHAT_ID' => trim($this->telegramChatId),
        ];

        if ($values === [
            'TELEGRAM_BOT_TOKEN' => (string) EnvFile::get('TELEGRAM_BOT_TOKEN'),
            'TELEGRAM_CHAT_ID' => (string) EnvFile::get('TELEGRAM_CHAT_ID'),
        ]) {
            return true;
        }

        try {
            EnvFile::set($values);
        } catch (\RuntimeException) {
            return false;
        }

        Artisan::call('config:clear');

        AdminActivity::log('updated', 'Telegram settings updated');

        return true;
    }

    /**
     * Sends a message with what is typed in the Telegram card, saved or not, so
     * a wrong token or chat id is found before it silently drops a real contact.
     */
    public function sendTelegramTest(): void
    {
        $sent = Telegram::send(
            'Hello from '.config('app.name').' — Telegram is connected.',
            $this->telegramBotToken,
            $this->telegramChatId,
        );

        $this->dispatch('notify', type: $sent ? 'success' : 'error', message: $sent
            ? 'Test message sent. Check your Telegram chat.'
            : 'Could not send. Check the bot token and chat id (and that the bot is in the chat).');
    }

    protected function loadConstants(): void
    {
        $this->constants = collect(json_decode(Setting::get('constants', '[]') ?: '[]', true) ?: [])
            ->map(fn (array $pair) => [...$pair, 'type' => in_array($pair['type'] ?? null, ['textarea', 'file'], true) ? $pair['type'] : 'textarea'])
            ->all();
    }

    public function addConstant(): void
    {
        $this->constants[] = ['key' => '', 'type' => 'textarea', 'value' => ''];
        $this->openConstants[] = array_key_last($this->constants);
    }

    public function removeConstant(int $index): void
    {
        unset($this->constants[$index]);
        $this->constants = array_values($this->constants);

        $this->openConstants = collect($this->openConstants)
            ->reject(fn ($i) => $i === $index)
            ->map(fn ($i) => $i > $index ? $i - 1 : $i)
            ->unique()
            ->values()
            ->all();
    }

    public function setConstantType(int $index, string $type): void
    {
        if (! array_key_exists($index, $this->constants) || ! in_array($type, ['textarea', 'file'], true)) {
            return;
        }

        $this->constants[$index]['type'] = $type;
    }

    public function toggleConstant(int $index): void
    {
        if (in_array($index, $this->openConstants, true)) {
            $this->openConstants = array_values(array_diff($this->openConstants, [$index]));
        } else {
            $this->openConstants[] = $index;
        }
    }

    public function addCodeRule(): void
    {
        $rule = ['id' => CustomCode::newId(), 'name' => '', 'head' => '', 'body' => ''];
        $this->codeRules[] = $rule;
        $this->openCodeRules[] = $rule['id'];
    }

    public function toggleCodeRule(string $id): void
    {
        $this->openCodeRules = in_array($id, $this->openCodeRules, true)
            ? array_values(array_diff($this->openCodeRules, [$id]))
            : [...$this->openCodeRules, $id];
    }

    public function removeCodeRule(int $index): void
    {
        $removed = $this->codeRules[$index]['id'] ?? null;
        unset($this->codeRules[$index]);
        $this->codeRules = array_values($this->codeRules);
        $this->openCodeRules = array_values(array_diff($this->openCodeRules, [$removed]));
    }

    public function updated(string $name, mixed $value): void
    {
        if (preg_match('/^constants\.\d+\.key$/', $name)) {
            $sanitized = preg_replace('/[^A-Za-z0-9_]/', '', preg_replace('/\s+/', '_', trim($value)));

            if ($sanitized !== $value) {
                data_set($this, $name, $sanitized);
            }
        }
    }

    /** Saves the Custom Code and Constant tabs (DB settings, not .env). */
    public function saveSiteData(): void
    {
        $this->validate([
            'settings.custom_head_code' => 'nullable|string',
            'settings.custom_body_code' => 'nullable|string',
            'constants' => ['array', function (string $attribute, mixed $value, \Closure $fail) {
                $keys = collect($value)->pluck('key')->filter()->map(fn ($key) => strtolower(trim($key)));

                if ($keys->count() !== $keys->unique()->count()) {
                    $fail('Constant keys must be unique.');
                }
            }],
            'constants.*.key' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_]*$/'],
            'constants.*.type' => 'nullable|in:textarea,file',
            'constants.*.value' => 'nullable|string|max:1000',
            'codeRules.*.name' => 'nullable|string|max:100',
            'codeRules.*.head' => 'nullable|string',
            'codeRules.*.body' => 'nullable|string',
        ]);

        foreach ($this->settings as $key => $value) {
            // The panel typeface is validated against the fonts that exist now.
            Setting::set($key, $key === 'admin_font' ? AdminFont::normalize($value) : $value);
        }

        $constants = collect($this->constants)->filter(fn (array $pair) => filled($pair['key'] ?? null))->values()->all();
        Setting::set('constants', json_encode($constants));

        // Rows with no code in either box are dropped rather than stored empty. The id is what pages
        // point at, so it is kept as-is across saves.
        $rules = collect($this->codeRules)
            ->filter(fn (array $rule) => filled($rule['head'] ?? null) || filled($rule['body'] ?? null))
            ->map(fn (array $rule) => [
                'id' => $rule['id'],
                'name' => trim((string) ($rule['name'] ?? '')),
                'head' => (string) ($rule['head'] ?? ''),
                'body' => (string) ($rule['body'] ?? ''),
            ])
            ->values()
            ->all();
        Setting::set(CustomCode::SETTING, json_encode($rules));

        if (! $this->persistTelegram()) {
            $this->dispatch('notify', type: 'error', message: 'Saved, but the Telegram details could not be written to .env.');

            return;
        }

        session()->flash('success', 'Saved.');
        $this->js('window.location.reload()');
    }

    public function render()
    {
        return view('livewire.admin.env.index', [
            'groupedSettings' => Setting::whereIn('group', ['localization', 'pagination', 'newsletter'])->get()->groupBy('group'),
            'colorSettings' => Setting::where('group', 'colors')->get(),
            'adminFontOptions' => AdminFont::options(),
        ])->layout('layouts.admin', ['title' => 'Developer Tools']);
    }
}
