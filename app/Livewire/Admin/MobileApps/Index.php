<?php

namespace App\Livewire\Admin\MobileApps;

use App\Models\MobileApp;
use App\Support\AdminActivity;
use Livewire\Component;

class Index extends Component
{
    public string $googlePlayUrl = '';

    public string $appStoreUrl = '';

    public function mount(): void
    {
        $this->googlePlayUrl = MobileApp::where('platform', 'google_play')->value('url') ?? '';
        $this->appStoreUrl = MobileApp::where('platform', 'app_store')->value('url') ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'googlePlayUrl' => ['nullable', 'url', 'max:2048'],
            'appStoreUrl' => ['nullable', 'url', 'max:2048'],
        ]);

        MobileApp::updateOrCreate(
            ['platform' => 'google_play'],
            ['url' => filled($this->googlePlayUrl) ? trim($this->googlePlayUrl) : null],
        );
        MobileApp::updateOrCreate(
            ['platform' => 'app_store'],
            ['url' => filled($this->appStoreUrl) ? trim($this->appStoreUrl) : null],
        );

        AdminActivity::log('updated', 'Mobile app download links updated');

        $this->mount();
        $this->dispatch('notify', message: 'Mobile app links saved.');
    }

    public function render()
    {
        return view('livewire.admin.mobile-apps.index')->layout('layouts.admin', ['title' => 'Mobile Apps']);
    }
}
