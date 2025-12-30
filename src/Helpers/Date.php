<?php

namespace Makis83\LaravelBundle\Helpers;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Helper classes for working with date.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-11-28
 * Time: 22:53
 */
class Date
{
    /**
     * Returns Carbon instance for the given date.
     *
     * @param null|string|int|float|DateTimeInterface|Carbon $date Original date
     * @return null|Carbon Carbon instance or null
     * @throws InvalidFormatException If date is invalid
     */
    public static function toCarbon(null|string|int|float|DateTimeInterface|Carbon $date): ?Carbon
    {
        // Check if date is null
        if (null === $date) {
            return null;
        }

        // Check if date is already a Carbon instance
        if ($date instanceof Carbon) {
            return $date;
        }

        // Check if date is a float or integer
        if (is_float($date) || is_int($date)) {
            return Carbon::createFromTimestamp($date);
        }

        // Default case
        return Carbon::parse($date);
    }
}
