<?php

namespace Makis83\LaravelBundle\Traits\models;

use Makis83\Helpers\Text;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Makis83\LaravelBundle\Traits\UsesSingleton;
use Illuminate\Contracts\Database\Query\Expression;
use Makis83\LaravelBundle\Exceptions\InvalidUsageException;

/**
 * Provides methods for working with database.
 * This trait should be used within models.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-02-08
 * Time: 13:21
 */
trait UsesDb
{
    use UsesSingleton;


    /**
     * Validates that the class using this trait extends the Model class.
     *
     * @throws InvalidUsageException if the class does not extend Model
     */
    private static function validateModelUsage(): void
    {
        if (!is_subclass_of(static::class, Model::class) && static::class !== Model::class) {
            throw new InvalidUsageException(
                static::class,
                self::class,
                Model::class
            );
        }
    }


    /**
     * Get database driver name.
     *
     * @return string Database driver name (```mysql```, ```mariadb```, ```pgsql```, ```sqlite``` or ```sqlsrv```)
     * @throws InvalidUsageException if the class does not extend Model class
     */
    public static function dbDriverName(): string
    {
        // Ensure the trait is applied to a model
        static::validateModelUsage();

        // Get model object
        $model = static::getInstance();

        // Result
        return Cache::remember(
            'db-driver-model-' . Text::classNameToId($model::class),
            60,
            static function () use ($model) {
                // Get driver name
                $driverName = $model->getConnection()->getDriverName();

                // Since MariaDB is a fork of MySQL, we need to check the database version
                if ('mysql' === $driverName) {
                    $databaseVersion = DB::selectOne('SELECT VERSION() as version')->version;
                    if (str_contains(mb_strtolower($databaseVersion), 'mariadb')) {
                        $driverName = 'mariadb';
                    }
                }

                // Result
                return $driverName;
            }
        );
    }


    /**
     * Get table name without prefix.
     *
     * @return string Table name without prefix
     * @throws InvalidUsageException if the class does not extend Model class
     */
    public static function dbTableName(): string
    {
        static::validateModelUsage();
        return static::getInstance()->getTable();
    }


    /**
     * Get table name with prefix.
     *
     * @return string Full table name with prefix
     * @throws InvalidUsageException if the class does not extend Model class
     */
    public static function dbFullTableName(): string
    {
        // Get a short table name
        $model = static::getInstance();
        $tableName = static::dbTableName();

        // Full table name
        return $model->getConnection()->getTablePrefix() . $tableName;
    }


    /**
     * Get full DB column name (with full table name).
     *
     * @param string $column Column name
     * @return string|Expression Full column name
     * @throws InvalidUsageException if the class does not extend Model class
     */
    public static function dbFullColumnName(string $column): string|Expression
    {
        return static::dbFullTableName() . '.' . $column;
    }
}
