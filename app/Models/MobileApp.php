<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class MobileApp extends Model
{
    protected $fillable = ['platform', 'url'];

    /** @var array<int, array<string, string|null>> */
    private static array $urls = [];

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /** @return Collection<string, string> */
    public static function urlsCached(): Collection
    {
        $key = spl_object_id(Cache::getFacadeRoot());

        if (! array_key_exists($key, self::$urls)) {
            self::$urls[$key] = Cache::rememberForever(
                'mobile-apps:all:v'.self::cacheVersion(),
                fn () => static::query()->orderBy('platform')->get(['platform', 'url'])->toArray(),
            );
        }

        return collect(self::$urls[$key])->pluck('url', 'platform')->filter();
    }

    public static function url(string $platform): ?string
    {
        return self::urlsCached()->get($platform);
    }

    public static function cacheVersion(): int
    {
        return (int) Cache::get('mobile-apps:cache-version', 0);
    }

    public static function flushCache(): void
    {
        self::$urls = [];
        Cache::forever('mobile-apps:cache-version', self::cacheVersion() + 1);
    }
}
