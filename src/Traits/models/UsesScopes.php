<?php

namespace Makis83\LaravelBundle\Traits\models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Makis83\LaravelBundle\Helpers\Date;
use Illuminate\Database\Eloquent\Builder;
use Makis83\LaravelBundle\Models\BaseModel;
use Carbon\Exceptions\InvalidFormatException;
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
 * @method Builder<static>|static filterByTimeRange(string $column, null|string|int|DateTimeInterface|Carbon $from, null|string|int|DateTimeInterface|Carbon $to, ?string $alias = null) Filter items by time range
 * @method Builder<static>|static filterByTimePeriod(string $column, null|string $period, ?string $alias = null) Filter items by time period
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
 * @see static::scopeFilterByTimeRange()
 * @see static::scopeFilterByTimePeriod()
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
     * Filter data by time range.
     *
     * @param Builder<static> $query Query builder
     * @param string $column Column name
     * @param null|string|int|DateTimeInterface|Carbon $from From
     * @param null|string|int|DateTimeInterface|Carbon $to To
     * @param null|string $alias Table alias
     * @return Builder<static> Filtered query builder
     * @throws InvalidFormatException If dates are invalid
     * @throws ExtendedException If the first date is greater than the second one
     */
    public function scopeFilterByTimeRange(
        Builder $query,
        string $column,
        null|string|int|DateTimeInterface|Carbon $from = null,
        null|string|int|DateTimeInterface|Carbon $to = null,
        null|string $alias = null
    ): Builder {
        // Get full column name
        $tableName = $this->getTable();
        $column = $alias ? $alias . '.' . $column : $tableName . '.' . $column;

        // Get Carbon instances for dates
        $fromCarbon = Date::toCarbon($from);
        $toCarbon = Date::toCarbon($to);

        if ($fromCarbon !== null) {
            $query->where($column, '>=', $fromCarbon);
        }

        if ($toCarbon !== null) {
            // Validate
            if (isset($fromCarbon) && $fromCarbon->gt($toCarbon)) {
                throw new ExtendedException(
                    422,
                    'Invalid date range. End date cannot be earlier than start date.'
                );
            }

            $query->where($column, '<=', $toCarbon);
        }

        return $query;
    }


    /**
     * Filter data by time period.
     *
     * @param Builder<static> $query Query builder
     * @param string $column Column name
     * @param null|string $period Time period
     * @param null|string $alias Table alias
     * @return Builder<static> Filtered query builder
     */
    public function scopeFilterByTimePeriod(
        Builder $query,
        string $column,
        string $period = null,
        null|string $alias = null
    ): Builder {
        // Validate period
        $periods = ['today', '24h', 'yesterday', 'this_week', 'last_week', '7d', 'this_month', 'last_month', '1month'];
        if ((null !== $period) && !in_array($period, $periods, true)) {
            throw new InvalidFormatException('Invalid time period.');
        }

        // Get full column name
        $tableName = $this->getTable();
        $column = $alias ? $alias . '.' . $column : $tableName . '.' . $column;

        // Get current date
        $now = Carbon::now();

        // Filter items
        switch ($period) {
            case '24h':
                $from = $now->subHours(24);
                $query->where($column, '>=', $from);
                break;
            case 'yesterday':
                $yesterday = $now->subDay();
                $query->whereDate($column, $yesterday->toDateString());
                break;
            case 'this_week':
                $startOfWeek = $now->startOfWeek();
                $endOfWeek = $now->endOfWeek();
                $query->whereBetween($column, [$startOfWeek, $endOfWeek]);
                break;
            case 'last_week':
                $startOfLastWeek = $now->subWeek()->startOfWeek();
                $endOfLastWeek = $now->endOfWeek();
                $query->whereBetween($column, [$startOfLastWeek, $endOfLastWeek]);
                break;
            case '7d':
                $from = $now->subDays(7);
                $query->where($column, '>=', $from);
                break;
            case 'this_month':
                $startOfMonth = $now->startOfMonth();
                $endOfMonth = $now->endOfMonth();
                $query->whereBetween($column, [$startOfMonth, $endOfMonth]);
                break;
            case 'last_month':
                $startOfLastMonth = $now->subMonth()->startOfMonth();
                $endOfLastMonth = $now->endOfMonth();
                $query->whereBetween($column, [$startOfLastMonth, $endOfLastMonth]);
                break;
            case '1month':
                $from = $now->subMonth();
                $query->where($column, '>=', $from);
                break;
            default:
                // 'today'
                $query->whereDate($column, $now->toDateString());
                break;
        }

        // Result
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
