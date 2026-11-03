<?php

namespace App\Models;

use App\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A vacancy on the Careers page. Published by flipping status to "open"; a post
 * stops taking applications once it is closed or its deadline has passed.
 */
class JobPost extends Model
{
    use HasSlug, SoftDeletes;

    public const STATUSES = ['draft', 'open', 'closed'];

    public const EMPLOYMENT_TYPES = [
        'full-time' => 'Full-time',
        'part-time' => 'Part-time',
        'contract' => 'Contract',
        'internship' => 'Internship',
    ];

    protected $fillable = [
        'title', 'slug', 'department', 'location', 'employment_type', 'vacancy',
        'deadline', 'description', 'requirements', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'vacancy' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** Published and not past its deadline (the deadline day itself still counts). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open')
            ->where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', now()->toDateString()));
    }

    public function isAcceptingApplications(): bool
    {
        return $this->status === 'open' && (! $this->deadline || ! $this->deadline->endOfDay()->isPast());
    }

    public function employmentTypeLabel(): string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? Str::headline((string) $this->employment_type);
    }
}
