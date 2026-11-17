<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A reusable block saved from the builder (a `builders` row of type 'block'). */
class BuilderBlock extends Model
{
    protected $table = 'builders';

    protected $fillable = ['name', 'content', 'user_id'];

    protected static function booted(): void
    {
        static::addGlobalScope('block', fn (Builder $q) => $q->where('builders.type', 'block'));
        static::creating(fn (self $m) => $m->type = 'block');
    }
}
