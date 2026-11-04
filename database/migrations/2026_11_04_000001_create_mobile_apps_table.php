<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_apps', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->unique();
            $table->text('url')->nullable();
            $table->timestamps();
        });

        $legacyLinks = DB::table('social_links')
            ->whereIn('platform', ['google_play', 'app_store'])
            ->get(['platform', 'url', 'created_at']);

        foreach ($legacyLinks as $link) {
            DB::table('mobile_apps')->updateOrInsert(
                ['platform' => $link->platform],
                [
                    'url' => $link->url,
                    'created_at' => $link->created_at ?? now(),
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('social_links')->whereIn('platform', ['google_play', 'app_store'])->delete();
        Cache::forget('social-links:all');
    }

    public function down(): void
    {
        foreach (DB::table('mobile_apps')->get(['platform', 'url', 'created_at']) as $app) {
            DB::table('social_links')->updateOrInsert(
                ['platform' => $app->platform],
                [
                    'label' => $app->platform === 'google_play' ? 'Google Play' : 'App Store',
                    'url' => $app->url,
                    'sort_order' => $app->platform === 'google_play' ? 1000 : 1001,
                    'created_at' => $app->created_at ?? now(),
                    'updated_at' => now(),
                ],
            );
        }

        Schema::dropIfExists('mobile_apps');
        Cache::forget('social-links:all');
    }
};
