<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The GrapesJS page builder's output for one page in one locale. */
class PageBuild extends Model
{
    protected $fillable = ['page_id', 'locale', 'project', 'html', 'css'];

    protected $casts = ['project' => 'array'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
