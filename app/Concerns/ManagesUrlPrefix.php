<?php

namespace App\Concerns;

use App\Support\AdminActivity;
use App\Support\UrlStructure;
use Illuminate\Support\Facades\Gate;

/**
 * The "URL Structure" modal of a list screen (Products, Posts, Services): the
 * public prefix for that kind of record. The component says which kind it is
 * through urlPrefixType() — a key of UrlStructure::TYPES.
 */
trait ManagesUrlPrefix
{
    public string $urlPrefix = '';

    abstract protected function urlPrefixType(): string;

    public function mountManagesUrlPrefix(): void
    {
        $this->urlPrefix = UrlStructure::prefix($this->urlPrefixType());
    }

    public function saveUrlPrefix(): void
    {
        // The button and modal are hidden from staff in the view, but a public
        // Livewire method is still callable directly — this is the enforcement.
        Gate::authorize('access-admin-system');

        $type = $this->urlPrefixType();

        $this->validate([
            'urlPrefix' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]*$/', function (string $attribute, mixed $value, \Closure $fail) use ($type) {
                if ($message = UrlStructure::error($type, (string) $value)) {
                    $fail($message);
                }
            }],
        ], [], ['urlPrefix' => 'URL prefix']);

        UrlStructure::save($type, $this->urlPrefix);
        $this->urlPrefix = UrlStructure::prefix($type);

        AdminActivity::log('updated', 'URL structure updated: '.$type);

        $this->dispatch('close-modal', name: 'url-structure-settings');
        $this->dispatch('notify', message: 'URL structure saved.');
    }
}
