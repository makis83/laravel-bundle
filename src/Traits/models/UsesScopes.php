<?php
namespace Makis83\LaravelBundle\Traits\models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Makis83\LaravelBundle\Exceptions\ExtendedException;
use InvalidArgumentException as PHPInvalidArgumentException;

/**
 * Adds default model scopes.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-09-29
 * Time: 22:05
 *
 * @method Builder|static active() return only active items
 * @method Builder|static inactive() return only inactive items
 * @method Builder|static filterByStrictValues(string $column, null|string|int|float|array $values = [], ?string $alias = null) filter data by strict values
 * @method Builder|static filterByLikeValues(string $column, null|string|int|float|array $values = [], ?string $alias = null) filter items by given values using 'LIKE' operator
 * @method Builder|static filterByUnixtime(string $column, ?int $unixtimeMin = null, ?int $unixtimeMax = null, ?string $alias = null, ?Carbon $dateMin = null, ?Carbon $dateMax = null) filter items by min and max unixtime value
 * @method Builder|static sortByDemand(?string $sort = null) sort model by the provided sort settings
 * @method Builder|static paginateByDemand(string|int $page = 1, string|int $perPage = 0) paginate data
 * @method Builder|static sortAndPaginate(?string $sort = null, string|int $page = 1, string|int $perPage = 0) sort and paginate model's data
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
    /**
     * @var array<string, array> $sort sort settings for using in a data provider
     */
    private static array $sortDefault = [
        'attributes' => ['id'],
        'defaultOrder' => [
            'id' => 'ASC'
        ]
    ];


    /**
     * Parse sort attributes.
     * @return array special array with column alias as array key and full column name as value
     * @throws PHPInvalidArgumentException
     */
    protected static function parseSortAttributes(): array
    {
        // get model sort settings
        if (
            property_exists(static::class, 'sort') &&
            is_array(static::$sort) &&
            !empty(static::$sort)
        ) {
            $modelSortSettings = static::$sort;
        } else {
            $modelSortSettings = static::$sortDefault;
        }

        // initial sort attributes array
        $sortAttributes = [];

        // loop through raw model sort attributes
        if (
            array_key_exists('attributes', $modelSortSettings) &&
            is_array($modelSortSettings['attributes']) &&
            !empty($modelSortSettings['attributes'])
        ) {
            foreach ($modelSortSettings['attributes'] as $key => $value) {
                // check if key is numeric
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
     * @param static|Builder $query Query builder
     * @param null|string $sort Sort sequence
     * @return static|Builder query builder
     */
    public function scopeSortByDemand(self|Builder $query, ?string $sort = null): static|Builder
    {
        // get model sort settings
        if (
            property_exists(static::class, 'sort') &&
            is_array(static::$sort) &&
            !empty(static::$sort)
        ) {
            $modelSortSettings = static::$sort;
        } else {
            $modelSortSettings = static::$sortDefault;
        }

        // generate an array of sort attributes from data provided by user
        $userSortSettings = static::parseSortSequence($sort);

        // get sort settings from model if user order is not specified
        if (
            empty($userSortSettings) &&
            is_array($modelSortSettings) &&
            array_key_exists('defaultOrder', $modelSortSettings) &&
            !empty($modelSortSettings['defaultOrder'])
        ) {
            $userSortSettings = $modelSortSettings['defaultOrder'];
        }

        // get available attributes for model sort
        $modelAvailableSortAttributes = static::parseSortAttributes();

        // loop through user sort settings
        foreach ($userSortSettings as $column => $order) {
            // skip if column is not allowed
            if (!array_key_exists($column, $modelAvailableSortAttributes)) {
                continue;
            }

            // sort
            $query->orderBy(DB::raw($modelAvailableSortAttributes[$column]), $order);
        }

        // return builder
        return $query;
    }


    /**
     * Paginate data by demand.
     * @param static|Builder $query query builder
     * @param string|int $page page to show
     * @param string|int $perPage items per page
     * @return static|Builder query builder
     */
    public function scopePaginateByDemand(
        self|Builder $query,
        string|int $page = 1,
        string|int $perPage = 0
    ): static|Builder {
        // cast arguments to int
        $page = (int) $page;
        $perPage = (int) $perPage;

        // deal with pagination
        if (($page >= 1) && ($perPage >= 1)) {
            // $query->offset(($page - 1) * $perPage)->limit($perPage);
            $query->paginate(perPage: $perPage, page: $page);
        }

        // return builder
        return $query;
    }


    /**
     * Return only active items.
     * @param static|Builder $query
     * @return static|Builder query builder
     */
    public function scopeActive(self|Builder $query): static|Builder
    {
        return $query->where($this->getTable() . '.active', 1);
    }


    /**
     * Return only inactive items.
     * @param static|Builder $query
     * @return static|Builder query builder
     */
    public function scopeInactive(self|Builder $query): static|Builder
    {
        return $query->where($this->getTable() . '.active', 0);
    }


    /**
     * Filter data by strict values.
     * @param static|Builder $query query builder
     * @param string $column column name
     * @param null|string|int|float|array<int, null|string|int|float> $values values
     * @param null|string $alias table alias
     * @return static|Builder query builder
     */
    public function scopeFilterByStrictValues(
        self|Builder $query,
        string $column,
        null|string|int|float|array $values = [],
        ?string $alias = null
    ): static|Builder {
        // check if values is not an array
        if (!is_array($values)) {
            $values = [$values];
        }

        // filter by values
        if (!empty($values)) {
            // get full column name
            $tableName = static::getTableName();
            $column = $alias ? $alias . '.' . $column : $tableName . '.' . $column;

            // apply conditions
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

        // default case
        return $query;
    }


    /**
     * Filter items by given values using 'LIKE' operator.
     * @param static|Builder $query query builder
     * @param string $column column name
     * @param null|string|int|float|array<int, null|string|int|float> $values values
     * @param null|string $alias table alias
     * @return static|Builder query builder
     */
    public function scopeFilterByLikeValues(
        self|Builder $query,
        string $column,
        null|string|int|float|array $values = [],
        ?string $alias = null
    ): static|Builder {
        // check if values is not an array
        if (!is_array($values)) {
            $values = [$values];
        }

        // filter
        if (!empty($values)) {
            // get app charset
            $charset = config('app.charset');

            // get full column name
            $tableName = static::getTableName();
            $column = $alias ? $alias . '.' . $column : $tableName . '.' . $column;

            // result query
            return $query->where(static function (self|Builder $query) use ($charset, $column, $values) {
                // loop through values and populate conditions array
                foreach ($values as $value) {
                    if (null === $value) {
                        $query->orWhereNull($column);
                    } else {
                        // skip if the value is too short
                        if (!is_string($value) || (mb_strlen($value, $charset) < 2)) {
                            continue;
                        }

                        // check if this value has a percent char at the beginning or at the end
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
     * @param static|Builder $query query builder
     * @param string $column column name
     * @param null|string|int $unixtimeMin min unixtime
     * @param null|string|int $unixtimeMax max unixtime
     * @param null|string $alias table alias
     * @param Carbon|null $dateMin min allowed date
     * @param Carbon|null $dateMax max allowed date
     * @return static|Builder query builder
     * @throws ExtendedException
     */
    public function scopeFilterByUnixtime(
        self|Builder $query,
        string $column = 'created_at',
        null|string|int $unixtimeMin = null,
        null|string|int $unixtimeMax = null,
        ?string $alias = null,
        ?Carbon $dateMin = null,
        ?Carbon $dateMax = null
    ): static|Builder {
        // get full column name
        $tableName = $this->getTable();
        $column = $alias ? $alias . '.' . $column : $tableName . '.' . $column;

        // filter by start unixtime
        if (isset($unixtimeMin)) {
            // get date object
            $dateFrom = Carbon::createFromTimestamp($unixtimeMin);

            // validate
            if ($dateMin && $dateFrom->lt($dateMin)) {
                throw new ExtendedException(
                    422,
                    __('response.model.common.error.unixtime_from_too_early', [
                        'date' => $dateMin->toDateTimeString()
                    ])
                );
            }

            // filter
            $query->where($column, '>=', $dateFrom->toDateTimeString());
        }

        // filter by end unixtime
        if (isset($unixtimeMax)) {
            // get date object
            $dateTo = Carbon::createFromTimestamp($unixtimeMax);

            // validate
            if ($dateMax && $dateTo->gt($dateMax)) {
                throw new ExtendedException(
                    422,
                    __('response.model.common.error.unixtime_to_too_late')
                );
            }

            if (isset($dateFrom) && $dateFrom->gt($dateTo)) {
                throw new ExtendedException(
                    422,
                    __('response.model.common.error.unixtime_from_too_late')
                );
            }

            // filter
            $query->where($column, '<=', $dateTo->toDateString());
        }

        // return query
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
     * @param string|string[] $values array with values
     * @param int $minLength min length
     * @param bool $toLowercase whether to make all values lowercase
     * @return array normalized array
     */
    public static function normalizeFilterArray(
        array $values = [],
        int $minLength = 2,
        bool $toLowercase = false
    ): array {
        // transform a string value to array
        if (is_string($values)) {
            $values = [$values];
        }

        // check if values array is empty
        if (empty($values)) {
            return $values;
        }

        // populate normalized array
        $valuesNormalized = [];
        foreach ($values as $value) {
            if (is_string($value) && (!$minLength || (mb_strlen($value, config('app.charset')) >= $minLength))) {
                $valuesNormalized[] = $toLowercase ? mb_strtolower($value, config('app.charset')) : $value;
            }
        }

        // clean from duplicates
        return array_keys(array_flip($valuesNormalized));
    }
}
