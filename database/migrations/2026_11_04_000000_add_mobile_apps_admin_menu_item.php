<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $parentId = DB::table('menu_items')
            ->where('group', 'admin-sidebar')
            ->where('is_group', true)
            ->where('label', 'Library & System')
            ->value('id');

        if (! $parentId || DB::table('menu_items')->where('route_name', 'admin.mobile-apps')->exists()) {
            return;
        }

        $socialOrder = DB::table('menu_items')
            ->where('parent_id', $parentId)
            ->where('route_name', 'admin.social')
            ->value('sort_order');

        $sortOrder = ($socialOrder ?? 0) + 1;

        DB::table('menu_items')
            ->where('parent_id', $parentId)
            ->where('sort_order', '>=', $sortOrder)
            ->increment('sort_order');

        DB::table('menu_items')->insert([
            'group' => 'admin-sidebar',
            'parent_id' => $parentId,
            'is_group' => false,
            'label' => 'Mobile Apps',
            'icon' => 'device-phone-mobile',
            'route_name' => 'admin.mobile-apps',
            'sort_order' => $sortOrder,
            'is_active' => true,
            'is_short_menu' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::forget('admin-menu:items');
        Cache::forget('admin-menu:short-items');
    }

    public function down(): void
    {
        DB::table('menu_items')->where('route_name', 'admin.mobile-apps')->delete();

        Cache::forget('admin-menu:items');
        Cache::forget('admin-menu:short-items');
    }
};
