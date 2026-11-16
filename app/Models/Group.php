<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A named group filed under a Type (Product / Post / Gallery …), managed from
 * Accessories → Groups the same way Categories are. The Media Library files
 * gallery images into the groups of the Gallery type.
 */
class Group extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['name', 'type_id', 'status', 'sort_order'];

    protected static function booted(): void
    {
        // media_library keeps no foreign key to a group (that table is created
        // long before this one), so detach its rows here.
        static::deleting(function (Group $group) {
            MediaLibrary::where('gallery_group_id', $group->id)->update(['gallery_group_id' => null]);
        });
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(MediaLibrary::class, 'gallery_group_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** Groups filed under the Gallery type — what the Media Library offers. */
    public function scopeGallery(Builder $query): Builder
    {
        return $query->where('type_id', Type::idFor(Type::GALLERY));
    }
}
