<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A reusable block saved from the GrapesJS page builder. */
class PageBlock extends Model
{
    protected $fillable = ['name', 'content', 'user_id'];
}
