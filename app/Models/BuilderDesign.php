<?php

namespace App\Models;

use App\Concerns\HasSlug;
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

    /** The locale to read: the asked one if it has its own design, else the primary one. */
    public function localeFor(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return filled($this->html[$locale] ?? null) ? $locale : Locale::primary();
    }

    /** Locales that have a saved design. */
    public function locales(): array
    {
        return array_keys(array_filter($this->html ?? [], 'filled'));
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
        $locale = $this->localeFor($locale);

        return '<style>'.($this->css[$locale] ?? '').'</style>'.($this->html[$locale] ?? '');
    }
}
