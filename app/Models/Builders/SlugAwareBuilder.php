<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * Query builder for models that use the HasSlug concern.
 *
 * Their slug is not a column — it lives in the `slugs` table — but a great deal
 * of code asks `Page::where('slug', $x)`, `->whereHas('page', fn ($q) =>
 * $q->where('slug', $x))` or `updateOrCreate(['slug' => $x])`. Rather than
 * rewriting every one of those, a constraint on the `slug` column is translated
 * here into the equivalent "has a slugs row matching" constraint.
 *
 * Only filters are translated. Selecting or ordering by `slug` has no column to
 * point at and has to go through the relation (`with('slugRecord')`).
 */
class SlugAwareBuilder extends Builder
{
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_array($column) && array_key_exists('slug', $column)) {
            return parent::where(function (Builder $query) use ($column) {
                foreach ($column as $key => $val) {
                    is_int($key) && is_array($val) ? $query->where(...$val) : $query->where($key, '=', $val);
                }
            }, null, null, $boolean);
        }

        if ($this->isSlugColumn($column)) {
            if (func_num_args() === 2) {
                [$value, $operator] = [$operator, '='];
            }

            return $this->has('slugRecord', '>=', 1, $boolean, fn ($slugs) => $slugs->where('slug', $operator, $value));
        }

        return parent::where(...array_slice([$column, $operator, $value, $boolean], 0, max(func_num_args(), 1)));
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        if ($this->isSlugColumn($column)) {
            return $this->has('slugRecord', $not ? '<' : '>=', 1, $boolean, fn ($slugs) => $slugs->whereIn('slug', $values));
        }

        $this->query->whereIn($column, $values, $boolean, $not);

        return $this;
    }

    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    protected function isSlugColumn(mixed $column): bool
    {
        return is_string($column) && ($column === 'slug' || $column === $this->model->getTable().'.slug');
    }
}
