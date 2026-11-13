<?php

use App\Support\Locale;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** SEO copy that is translatable (stored as a per-locale JSON map). */
    private const TRANSLATABLE = ['seo_title', 'seo_description', 'og_title', 'og_description', 'twitter_title', 'twitter_description'];

    /** Every SEO column that used to live on `pages`. */
    private const LEGACY_COLUMNS = [
        'seo_title', 'seo_description', 'og_title', 'og_description', 'twitter_title', 'twitter_description',
        'og_image', 'twitter_image', 'no_index', 'no_follow', 'canonical_base', 'canonical_slug',
    ];

    /**
     * Run the migrations.
     *
     * One row of search/social metadata per page (a product's, post's or category's
     * page included — they all own a `pages` row). The page owns the relation and
     * creates the row with itself; a blank row means "no SEO copy of its own", and
     * the site-wide defaults in Settings apply.
     */
    public function up(): void
    {
        Schema::create('seos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->unique()->constrained('pages')->cascadeOnDelete();
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->json('og_title')->nullable();
            $table->json('og_description')->nullable();
            $table->json('twitter_title')->nullable();
            $table->json('twitter_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_image')->nullable();
            $table->boolean('no_index')->default(false);
            $table->boolean('no_follow')->default(false);
            $table->string('canonical_base')->nullable();
            $table->string('canonical_slug')->nullable();
            $table->timestamps();
        });

        $this->moveLegacyColumns();
        $this->giveEveryPageARow();
    }

    /**
     * Every page owns a row, filled or blank (the Page model creates one with each
     * new page). An install that already has pages gets a blank row for each one
     * the legacy copy above did not fill.
     */
    protected function giveEveryPageARow(): void
    {
        DB::table('pages')->orderBy('id')->select('id')->chunkById(200, function ($pages) {
            $have = DB::table('seos')->whereIn('page_id', $pages->pluck('id'))->pluck('page_id')->all();

            $rows = $pages->reject(fn ($page) => in_array($page->id, $have, true))
                ->map(fn ($page) => ['page_id' => $page->id, 'created_at' => now(), 'updated_at' => now()])
                ->values()
                ->all();

            if ($rows !== []) {
                DB::table('seos')->insert($rows);
            }
        });
    }

    /**
     * An install migrated before this table existed still has the twelve SEO
     * columns on `pages`: copy each page's values across (only pages that have
     * any), then drop the columns. A fresh install never created them, so this
     * is a no-op there.
     */
    protected function moveLegacyColumns(): void
    {
        if (! Schema::hasColumn('pages', 'seo_title')) {
            return;
        }

        $primary = Locale::primary();

        DB::table('pages')->orderBy('id')->chunkById(200, function ($pages) use ($primary) {
            foreach ($pages as $page) {
                $row = ['page_id' => $page->id, 'created_at' => now(), 'updated_at' => now()];
                $any = false;

                foreach (self::LEGACY_COLUMNS as $column) {
                    $value = $page->{$column} ?? null;

                    if (in_array($column, self::TRANSLATABLE, true) && is_string($value) && $value !== '') {
                        // Older rows hold a plain string instead of a locale map.
                        json_decode($value);
                        $value = json_last_error() === JSON_ERROR_NONE && str_starts_with(ltrim($value), '{')
                            ? $value
                            : json_encode([$primary => $value]);
                    }

                    if (in_array($column, ['no_index', 'no_follow'], true)) {
                        $value = (bool) $value;
                        $any = $any || $value;
                    } elseif (filled($value)) {
                        $any = true;
                    }

                    $row[$column] = $value === '' ? null : $value;
                }

                if ($any) {
                    DB::table('seos')->insert($row);
                }
            }
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(self::LEGACY_COLUMNS);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seos');
    }
};
