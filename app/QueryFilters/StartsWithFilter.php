<?php

namespace App\QueryFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValue;
use Spatie\QueryBuilder\Filters\Filter;

/** @implements Filter<Model> */
readonly class StartsWithFilter implements Filter
{
    /** @param literal-string $column */
    public function __construct(private string $column) {}

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            throw new InvalidFilterValue('Filter value must be a string.');
        }

        $column = $query->getQuery()->getGrammar()->wrap($this->column);
        $escapedValue = str_replace(
            ['!', '%', '_'],
            ['!!', '!%', '!_'],
            $value,
        );

        /** @var literal-string $sql */
        $sql = "{$column} LIKE ? ESCAPE '!'";

        $query->whereRaw(
            $sql,
            ["{$escapedValue}%"],
        );
    }
}
