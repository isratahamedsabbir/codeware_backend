<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Setting;
use App\Support\Features as FeatureList;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * A fresh install starts with only FeatureList::DEFAULT_ENABLED switched on;
     * everything else is hidden until an admin enables it. A row that already
     * exists is left alone, so re-seeding never undoes an admin's choice. Legacy
     * `settings` rows (feature_{key}) still win over the default so upgrading an
     * old database keeps what it had.
     */
    public function run(): void
    {
        foreach (FeatureList::ALL as $key => $label) {
            $default = in_array($key, FeatureList::DEFAULT_ENABLED, true);

            $feature = Feature::firstOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'is_enabled' => (bool) Setting::get(FeatureList::settingKey($key), $default),
                ]
            );

            if ($feature->label !== $label) {
                $feature->update(['label' => $label]);
            }
        }

        Setting::where('key', 'like', 'feature\_%')->delete();
    }
}
