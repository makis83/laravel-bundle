<?php

namespace Makis83\LaravelBundle\Traits\models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Builder;
use Makis83\LaravelBundle\Models\BaseModel;
use Makis83\LaravelBundle\Exceptions\ExtendedException;
use InvalidArgumentException as PHPInvalidArgumentException;

/**
 * Adds default model scopes.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-09-29
 * Time: 22:05
 *
 * @method Builder<static>|static active() Return only active items
 * @method Builder<static>|static inactive() Return only inactive items
 * @method Builder<static>|static filterByStrictValues(string $column, null|string|int|float|array<int, mixed> $values = [], ?string $alias = null) Filter data by strict values
 * @method Builder<static>|static filterByLikeValues(string $column, null|string|int|float|array<int, mixed> $values = [], ?string $alias = null) Filter items by given values using 'LIKE' operator
 * @method Builder<static>|static filterByUnixtime(string $column, ?int $unixtimeMin = null, ?int $unixtimeMax = null, ?string $alias = null, ?Carbon $dateMin = null, ?Carbon $dateMax = null) Filter items by min and max unixtime value
 * @method Builder<static>|static sortByDemand(?string $sort = null) Sort model by the provided sort settings
 * @method Builder<static>|static paginateByDemand(string|int $page = 1, string|int $perPage = 0) Paginate data
 * @method Builder<static>|static sortAndPaginate(?string $sort = null, string|int $page = 1, string|int $perPage = 0) Sort and paginate model's data
 *
 * @see static::scopeSortByDemand()
 * @see static::scopePaginateByDemand()
 * @see static::scopeSortAndPaginate()
 * @see static::scopeActive()
 * @see static::scopeInactive()
 * @see static::scopeFilterByStrictValues()
 * @see static::scopeFilterByLikeValues()
 * @see static::scopeFilterByUnixtime()
 */
trait UsesScopes
{
    use UsesDb;


    /**
     * Parse sort sequence used in API queries.
     *
     * @param null|string $sortSequence Sort string
     * ```
     * first_name,-last_name,-status,email
     * ```
     * @return array<string, 'asc'|'desc'> Special array with sort column names as index and sort order as value
     * ```
     * ['first_name' => 'asc', 'last_name' => 'desc', 'status' => 'desc', 'email' => 'asc']
     * ```
     */
    protected static function parseSortSequence(?string $sortSequence = null): array
    {
        // Check if sequence is empty
        if ('' === trim($sortSequence)) {
            return [];
        }

        // Split string into array
        $sortSequenceArr = explode(',', $sortSequence);
        $sortSequenceArr = array_map('trim', $sortSequenceArr);
        if (empty($sortSequenceArr)) {
            return [];
        }

        // Initial sort array
        $sort = [];

        // Get charset
        $charset = Config::get('app.charset', 'UTF-8');

        // Loop through sequence parts
        foreach ($sortSequenceArr as $part) {
            // Check if the first char is '-'
            if (0 === mb_strpos($part, '-', 0, $charset)) {
                $sort[mb_substr($part, 1, null, $charset)] = 'desc';
            } else {
                $sort[$part] = 'asc';
            }
        }

        // Return result array
        return $sort;
    }


    /**
     * Get model sort settings.
     *
     * @return array{attributes: string[], defaultOrder: array<string, string>} Model sort settings
     */
    protected static function getModelSortSettings(): array
    {
        if (
            property_exists(static::class, 'sort') &&
            is_array(static::$sort) &&
            !empty(static::$sort)
        ) {
            return static::$sort;
        }

        return BaseModel::$sort;
    }


    /**
     * Parse sort attributes.
     *
     * @return array<string, string> Special array with column alias as array key and full column name as value
     * @throws PHPInvalidArgumentException
     */
    protected static function parseSortAttributes(): array
    {
        // Get model sort settings
        $modelSortSettings = static::getModelSortSettings();

        // Initial sort attributes array
        $sortAttributes = [];

        // Loop through raw model sort attributes
        if (
            array_key_exists('attributes', $modelSortSettings) &&
            is_array($modelSortSettings['attributes']) &&
            !empty($modelSortSettings['attributes'])
        ) {
            foreach ($modelSortSettings['attributes'] as $key => $value) {
                // Check if key is numeric
                if (is_numeric($key)) {
                    $sortAttributes[$value] = $value;
                } elseif (is_array($value) && array_key_exists(0, $value) && is_callable($value[0])) {
                    $sortAttributes[$key] = app()->call(
                        $value[0],
                        (array_key_exists(1, $value) && is_array($value[1])) ? $value[1] : []
                    );
                } else {
                    $sortAttributes[$value] = $value;
                }
            }
        }

        // result
        return $sortAttributes;
    }


    /**
     * Sort data collection with provided settings.
     *
     * @param static|Builder<static> $query Query builder
     * @param null|string $sort Sort sequence
     * @return static|Builder<static> Query builder
     */
    public function scopeSortByDemand(self|Builder $query, ?string $sort = null): static|Builder
    {
        // Get model sort settings
        $modelSortSettings = static::getModelSortSettings();

        // Generate an array of sort attributes from data provided by user
        $userSortSettings = static::parseSortSequence($sort);

        // Get sort settings from model if user order is not specified
        if (
            empty($userSortSettings) &&
            array_key_exists('defaultOrder', $modelSortSettings) &&
            !empty($modelSortSettings['defaultOrder'])
        ) {
            $userSortSettings = $modelSortSettings['defaultOrder'];
        }

        // Get available attributes for model sort
        $modelAvailableSortAttributes = static::parseSortAttributes();

        // Loop through user sort settings
        foreach ($userSortSettings as $column => $order) {
            // Skip if column is not allowed
            if (!array_key_exists($column, $modelAvailableSortAttributes)) {
                continue;
            }

            // Sort
            $query->orderBy(DB::raw($modelAvailableSortAttributes[$column]), $order);
        }

        // Return builder
        return $query;
    }


    /**
     * Paginate data by demand.
     *
     * @param static|Builder<static> $query Query builder
     * @param string|int $page Page to show
     * @param string|int $perPage Number of items per page
     * @return static|Builder<static> Query builder
     */
    public function scopePaginateByDemand(
        self|Builder $query,
        string|int $page = 1,
        string|int $perPage = 0
    ): static|Builder {
        // Cast arguments to int
        $page = (int) $page;
        $perPage = (int) $perPage;

        // Deal with pagination
        if (($page >= 1) && ($perPage >= 1)) {
            // $query->offset(($page - 1) * $perPage)->limit($perPage);
            $query->paginate(perPage: $perPage, page: $page);
        }

        // Return builder
        return $query;
    }


    /**
     * Return only active items.
     *
     * @param static|Builder<static> $query Query builder
     * @return static|Builder Query builder
     */
    public function scopeActive(self|Builder $query): static|Builder
    {
        return $query->where($this->getTable() . '.active', 1);
    }


    /**
     * Return only inactive items.
     *
     * @param static|Builder<static> $query Query builder
     * @return static|Builder<static> Query builder
     */
    public function scopeInactive(self|Builder $query): static|Builder
    {
        return $query->where($this->getTable() . '.active', 0);
    }


    /**
     * Filter data by strict values.
     *
     * @param static|Builder<static> $query Query builder
     * @param string $column Column name
     * @param null|string|int|float|array<int, null|string|int|float> $values Values
     * @param null|string $alias Table alias
     * @return static|Builder<static> Query builder
     */
    public function scopeFilterByStrictValues(
        self|Builder $query,
        string $column,
        null|string|int|float|array $values = [],
        ?string $alias = null
    ): static|Builder {
        // Check if values is not an array
        if (!is_array($values)) {
            $values = [$values];
        }

        // Filter by values
        if (!empty($values)) {
            // Get full column name
            $tableName = $this->getTable();
            $column = $alias ? $alias . '.' . $column : $tableName . '.' . $column;

            // Apply conditions
            return $query->where(static function (self|Builder $query) use ($column, $values) {
                foreach ($values as $value) {
                    if (null === $value) {
                        $query->orWhereNull($column);
                    } else {
                        $query->orWhere($column, $value);
                    }
                }
            });
        }

        // Default case
        return $query;
    }


    /**
     * Filter items by given values using 'LIKE' operator.
     *
     * @param static|Builder<static> $query Query builder
     * @param string $column Column name
     * @param null|string|int|float|array<int, null|string|int|float> $values Values
     * @param null|string $alias Table alias
     * @return static|Builder<static> query builder
     */
    public function scopeFilterByLikeValues(
        self|Builder $query,
        string $column,
        null|string|int|float|array $values = [],
        ?string $alias = null
    ): static|Builder {
        // Cast values to array
        if (!is_array($values)) {
            $values = [$values];
        }

        // Filter
        if (!empty($values)) {
            // Get app charset
            $charset = Config::get('app.charset', 'UTF-8');

            // Get full column name
            $tableName = $this->getTable();
            $column = $alias ? $alias . '.' . $column : $tableName . '.' . $column;

            // Result query
            return $query->where(static function (self|Builder $query) use ($charset, $column, $values) {
                // Loop through values and populate conditions array
                foreach ($values as $value) {
                    if (null === $value) {
                        $query->orWhereNull($column);
                    } else {
                        // Skip if the value is too short
                        if (!is_string($value) || (mb_strlen($value, $charset) < 2)) {
                            continue;
                        }

                        // Check if this value has a percent char at the beginning or at the end
                        if ((0 === mb_strpos($value, '%', 0, $charset)) || ('%' === mb_substr($value, -1, 1, $charset))) {
                            $query->orWhereLike($column, $value);
                        } else {
                            $query->orWhere($column, $value);
                        }
                    }
                }
            });
        }

        // return query
        return $query;
    }


    /**
     * Filter items by unixtime.
     *
     * @param static|Builder<static> $query Query builder
     * @param string $column Column name
     * @param null|string|int $unixtimeMin Min unixtime
     * @param null|string|int $unixtimeMax Max unixtime
     * @param null|string $alias Table alias
     * @return static|Builder<static> Query builder
     * @throws ExtendedException If something went wrong
     */
    public function scopeFilterByUnixtime(
        self|Builder $query,
        string $column = 'created_at',
        null|string|int $unixtimeMin = null,
        null|string|int $unixtimeMax = null,
        ?string $alias = null
    ): static|Builder {
        // Get full column name
        $tableName = $this->getTable();
        $column = $alias ? $alias . '.' . $column : $tableName . '.' . $column;

        // Filter by start unixtime
        if (isset($unixtimeMin)) {
            $dateFrom = Carbon::createFromTimestamp($unixtimeMin);
            $query->where($column, '>=', $dateFrom->toDateTimeString());
        }

        // Filter by end unixtime
        if (isset($unixtimeMax)) {
            // Get date object
            $dateTo = Carbon::createFromTimestamp($unixtimeMax);

            // Validate
            if (isset($dateFrom) && $dateFrom->gt($dateTo)) {
                throw new ExtendedException(
                    422,
                    'Invalid date range. End date cannot be earlier than start date.'
                );
            }

            // Filter
            $query->where($column, '<=', $dateTo->toDateString());
        }

        // Return query
        return $query;
    }


    /**
     * Sort and paginate data.
     * @param static|Builder $query query builder
     * @param null|string $sort sort sequence
     * @param int|string $page current page
     * @param int|string $perPage items per page
     * @return static|Builder query builder
     */
    public function scopeSortAndPaginate(
        self|Builder $query,
        null|string $sort = null,
        int|string $page = 1,
        int|string $perPage = 0
    ): static|Builder {
        return $query->sortByDemand($sort)->paginateByDemand($page, $perPage);
    }


    /**
     * Normalize filter array.
     *
     * @param string|string[] $values Array with values
     * @param int $minLength Min length
     * @param bool $toLowercase Whether to make all values lowercase
     * @return array Normalized array
     */
    public static function normalizeFilterArray(
        array $values = [],
        int $minLength = 2,
        bool $toLowercase = false
    ): array {
        // Cast value to array
        if (is_string($values)) {
            $values = [$values];
        }

        // Check if values array is empty
        if (empty($values)) {
            return $values;
        }

        // Get charset
        $charset = Config::get('app.charset', 'UTF-8');

        // Populate normalized array
        $valuesNormalized = [];
        foreach ($values as $value) {
            if (is_string($value) && (!$minLength || (mb_strlen($value, $charset) >= $minLength))) {
                $valuesNormalized[] = $toLowercase ? mb_strtolower($value, $charset) : $value;
            }
        }

        // Clean from duplicates
        return array_keys(array_flip($valuesNormalized));
    }
}
