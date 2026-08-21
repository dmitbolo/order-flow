<?php

namespace App\QueryFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\Filters\Filter;

/** @implements Filter<Model> */
readonly class StartsWithFilter implements Filter
{
    public function __construct(private string $column) {}

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $query->where($this->column, 'LIKE', "{$value}%");
    }
}
