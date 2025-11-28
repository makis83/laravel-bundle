<?php

namespace Makis83\LaravelBundle\Helpers;

use Carbon\Carbon;
use DateTimeInterface;
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
     * @param null|string|int|DateTimeInterface|Carbon $date Original date
     * @return null|Carbon Carbon instance or null
     * @throws InvalidFormatException If date is invalid
     */
    public static function toCarbon(null|string|int|DateTimeInterface|Carbon $date): ?Carbon
    {
        if (null === $date) {
            return null;
        }

        if ($date instanceof Carbon) {
            return $date;
        }

        return Carbon::parse($date);
    }
}
