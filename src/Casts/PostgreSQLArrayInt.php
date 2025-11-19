<?php

namespace Makis83\LaravelBundle\Casts;

use JsonException;
use Makis83\Helpers\Data;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Provides getter and setter for PostgreSQL 'int[]' columns.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-10-25
 * Time: 17:23
 * @implements CastsAttributes<int[], int[]>
 */
class PostgreSQLArrayInt implements CastsAttributes
{
    /**
     * @inheritdoc
     * @throws JsonException if json_decode fails
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        // Check if value is null
        if (null === $value) {
            return null;
        }

        // Get data
        return Data::jsonDecode(str_replace(['{', '}'], ['[', ']'], $value));
    }


    /**
     * @inheritdoc
     * @throws JsonException if json_encode fails
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        // Check if value is null
        if (null === $value) {
            return null;
        }

        // Prepare data for storage
        return str_replace(['[', ']'], ['{', '}'], Data::jsonEncode($value));
    }
}
