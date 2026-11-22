<?php

namespace App\Models;

use App\Concerns\HasSlug;
use App\Support\Builder\DynamicBlocks;
use App\Support\Builder\Translations;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A GrapesJS design (a `builders` row of type 'design'). It stands on its own —
 * no page, no SEO — and is public at its own slug.
 *
 * `project`, `html` and `css` are JSON maps keyed by locale code.
 */
class BuilderDesign extends Model
{
    use HasSlug;

    protected $table = 'builders';

    protected $fillable = ['name', 'project', 'html', 'css', 'user_id'];

    protected $casts = ['project' => 'array', 'html' => 'array', 'css' => 'array'];

    protected static function booted(): void
    {
        static::addGlobalScope('design', fn (Builder $q) => $q->where('builders.type', 'design'));
        static::creating(fn (self $m) => $m->type = 'design');
    }

    /**
     * Which saved copy to print. One design serves every language: the primary copy,
     * with each text swapped for its inline translation (Translations). A copy saved
     * for a language of its own by an older version is still used, but only when the
     * primary copy has no inline text for that language.
     */
    public function localeFor(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if (Translations::has((string) ($this->html[Locale::primary()] ?? ''), $locale)) {
            return Locale::primary();
        }

        return filled($this->html[$locale] ?? null) ? $locale : Locale::primary();
    }

    /** Languages the design is available in: the primary one plus every language with inline text. */
    public function locales(): array
    {
        $inline = Translations::locales((string) ($this->html[Locale::primary()] ?? ''));
        $saved = array_keys(array_filter($this->html ?? [], 'filled'));

        return array_values(array_unique([...$saved, ...$inline]));
    }

    public function projectFor(string $locale): ?array
    {
        return $this->project[$locale] ?? $this->project[Locale::primary()] ?? null;
    }

    /** Saves one locale's output without touching the others. */
    public function saveLocale(string $locale, array $project, string $html, string $css): void
    {
        $this->update([
            'project' => [...($this->project ?? []), $locale => $project],
            'html' => [...($this->html ?? []), $locale => $html],
            'css' => [...($this->css ?? []), $locale => $css],
        ]);
    }

    /** What the public address prints: the CSS in a <style> followed by the HTML. */
    public function renderedHtml(?string $locale = null): string
    {
        $requested = $locale ?? app()->getLocale();
        $locale = $this->localeFor($locale);

        return '<style>'.($this->css[$locale] ?? '').'</style>'.DynamicBlocks::render(Translations::apply($this->html[$locale] ?? '', $requested));
    }
}
