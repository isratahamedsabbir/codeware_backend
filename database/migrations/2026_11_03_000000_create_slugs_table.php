<?php

use App\Support\Locale;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Tables that used to carry their own `slug` column, mapped to the model that
     * now owns the row's slug. Only an install migrated before the slugs table
     * existed still has these columns — a fresh one never created them.
     */
    private const LEGACY_COLUMNS = [
        'pages' => 'App\Models\Page',
        'services' => 'App\Models\Service',
        'vouchers' => 'App\Models\Voucher',
        'job_posts' => 'App\Models\JobPost',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // One row per slug-bearing record, whatever table it lives in. `slug` is
        // unique across ALL of them, so a page, a service and a brand can never
        // claim the same address; the (type, id) pair is unique too, so a record
        // owns exactly one slug. No foreign key — sluggable_id points into a
        // different table per type (a morph), so the models delete their own row.
        Schema::create('slugs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('sluggable_type');
            $table->unsignedBigInteger('sluggable_id');
            $table->timestamps();

            $table->unique(['sluggable_type', 'sluggable_id']);
        });

        $this->backfillLegacyColumns();
        $this->backfillTaxonomyNames();
    }

    /**
     * Copies each legacy `slug` column into the slugs table, then drops it. A
     * slug that is already taken by another table's row (the old columns were
     * only unique within their own table) gets a numeric suffix.
     */
    protected function backfillLegacyColumns(): void
    {
        foreach (self::LEGACY_COLUMNS as $table => $model) {
            if (! Schema::hasColumn($table, 'slug')) {
                continue;
            }

            DB::table($table)->orderBy('id')->select(['id', 'slug'])->each(function ($row) use ($model) {
                $this->claim($model, $row->id, (string) $row->slug, Str::kebab(class_basename($model)));
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropUnique(['slug']);
                $blueprint->dropColumn('slug');
            });
        }
    }

    /**
     * Brands and tags never stored a slug — it was derived from the name on every
     * read. Give each existing one a stored slug built the same way.
     */
    protected function backfillTaxonomyNames(): void
    {
        $models = ['brand' => 'App\Models\ProductBrand', 'tag' => 'App\Models\Tag'];
        $primary = Locale::primary();

        DB::table('categories')->whereIn('kind', array_keys($models))->orderBy('id')->each(function ($row) use ($models, $primary) {
            $names = json_decode((string) $row->name, true);
            $name = is_array($names) ? ($names[$primary] ?? $names['en'] ?? reset($names) ?: '') : (string) $row->name;

            $this->claim($models[$row->kind], $row->id, Str::slug((string) $name, '-'), $row->kind);
        });
    }

    protected function claim(string $model, int $id, string $desired, string $fallback): void
    {
        $base = Str::lower(trim($desired)) ?: $fallback;
        $slug = $base;

        for ($suffix = 2; DB::table('slugs')->where('slug', $slug)->exists(); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        DB::table('slugs')->insert([
            'slug' => $slug,
            'sluggable_type' => $model,
            'sluggable_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slugs');
    }
};
