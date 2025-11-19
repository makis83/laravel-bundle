<?php

namespace Makis83\LaravelBundle\Helpers;

use Makis83\Helpers\Text;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;

/**
 * Helper class for database operations.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-10-21
 * Time: 15:32
 */
class Database
{
    /**
     * Get database driver name.
     *
     * @param null|Model|class-string $object_or_class An object instance or a class name
     * @return string Database driver name (```mysql```, ```mariadb```, ```pgsql```, ```sqlite``` or ```sqlsrv```)
     */
    public static function driverName(null|Model|string $object_or_class = null): string
    {
        // Get cache key
        if (null === $object_or_class) {
            $key = 'db-driver-' . Config::get('database.default');
        } else {
            $className = ($object_or_class instanceof Model) ? $object_or_class::class : $object_or_class;
            $key = 'db-driver-model-' . Text::classNameToId($className);
        }

        return Cache::remember(
            $key,
            60,
            static function () use ($object_or_class) {
                // Get driver name
                if ($object_or_class instanceof Model) {
                    $driverName = $object_or_class->getConnection()->getDriverName();
                } elseif (
                    class_exists($object_or_class) &&
                    is_subclass_of($object_or_class, Model::class)
                ) {
                    $model = new $object_or_class();
                    $driverName = $model->getConnection()->getDriverName();
                } else {
                    $driverName = DB::getDriverName();
                }

                // Since MariaDB is a fork of MySQL, we need to check the database version
                if ('mysql' === $driverName) {
                    $databaseVersion = DB::selectOne('SELECT VERSION() as version')->version;
                    if (str_contains(mb_strtolower($databaseVersion), 'mariadb')) {
                        return 'mariadb';
                    }
                }

                // Result
                return $driverName;
            }
        );
    }
}
