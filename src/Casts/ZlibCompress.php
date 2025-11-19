<?php

namespace Makis83\LaravelBundle\Casts;

use Illuminate\Database\Eloquent\Model;
use Makis83\LaravelBundle\Helpers\Database;
use Safe\Exceptions\SafeExceptionInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Provides getter and setter for data compressed with ZLIB library.
 * Please use ```binary``` type for the column in the database.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-01-17
 * Time: 19:20
 * @implements CastsAttributes<string, string>
 */
class ZlibCompress implements CastsAttributes
{
    /**
     * @inheritdoc
     * @throws SafeExceptionInterface if the data cannot be uncompressed
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        // Check if value is null
        if (null === $value) {
            return null;
        }

        // Get database driver name
        $dbDriverName = Database::driverName($model);

        // Uncompress data
        return match ($dbDriverName) {
            'pgsql' => \Safe\stream_get_contents($value),
            default => \Safe\gzuncompress($value),
        };
    }


    /**
     * @inheritdoc
     * @throws SafeExceptionInterface if the data cannot be compressed
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        // Check if value is null
        if (null === $value) {
            return null;
        }

        // Get database driver name
        $dbDriverName = Database::driverName($model);

        // Compress data
        return match ($dbDriverName) {
            'pgsql' => str_replace("''", "'", pg_escape_bytea($value)),
            default => \Safe\gzcompress($value),
        };
    }
}
