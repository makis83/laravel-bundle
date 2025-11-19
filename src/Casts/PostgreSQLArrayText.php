<?php

namespace Makis83\LaravelBundle\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Provides getter and setter for PostgreSQL 'text[]' columns.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-10-25
 * Time: 19:42
 * @implements CastsAttributes<string[], string[]>
 */
class PostgreSQLArrayText implements CastsAttributes
{
    /**
     * @inheritdoc
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        // Check if value is null
        if (null === $value) {
            return null;
        }

        // Get data
        return explode(',', str_replace(['{', '}'], '', $value));
    }


    /**
     * @inheritdoc
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        // Check if value is null
        if (null === $value) {
            return null;
        }

        // Prepare data for storage
        return '{' . implode(',', $value) . '}';
    }
}
