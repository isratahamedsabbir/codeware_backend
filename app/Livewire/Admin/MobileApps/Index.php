<?php

namespace App\Livewire\Admin\MobileApps;

use App\Models\SocialLink;
use App\Support\AdminActivity;
use Livewire\Component;

class Index extends Component
{
    public string $googlePlayUrl = '';

    public string $appStoreUrl = '';

    public function mount(): void
    {
        $this->googlePlayUrl = SocialLink::where('platform', 'google_play')->value('url') ?? '';
        $this->appStoreUrl = SocialLink::where('platform', 'app_store')->value('url') ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'googlePlayUrl' => ['nullable', 'url', 'max:2048'],
            'appStoreUrl' => ['nullable', 'url', 'max:2048'],
        ]);

        SocialLink::updateOrCreate(
            ['platform' => 'google_play'],
            ['label' => 'Google Play', 'url' => filled($this->googlePlayUrl) ? trim($this->googlePlayUrl) : null, 'sort_order' => 1000],
        );
        SocialLink::updateOrCreate(
            ['platform' => 'app_store'],
            ['label' => 'App Store', 'url' => filled($this->appStoreUrl) ? trim($this->appStoreUrl) : null, 'sort_order' => 1001],
        );

        SocialLink::flushCache();
        AdminActivity::log('updated', 'Mobile app download links updated');

        $this->mount();
        $this->dispatch('notify', message: 'Mobile app links saved.');
    }

    public function render()
    {
        return view('livewire.admin.mobile-apps.index')->layout('layouts.admin', ['title' => 'Mobile Apps']);
    }
}
