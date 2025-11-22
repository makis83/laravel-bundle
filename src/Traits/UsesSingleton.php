<?php

namespace Makis83\LaravelBundle\Traits;

/**
 * Singleton realization for Laravel.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-10-16
 * Time: 15:24
 */
trait UsesSingleton
{
    /**
     * @var array<string, object> $instances Array of class object instances
     */
    protected static array $instances = [];


    /**
     * Refreshes the instance.
     *
     * @return static Class instance
     */
    final public static function cleanInstance(): static
    {
        return static::$instances[static::class] = new static();
    }


    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method Method name
     * @param array<int, mixed> $parameters Array of method arguments
     * @return mixed Method result
     */
    public static function __callStatic($method, $parameters): mixed
    {
        // Get class object instance
        $classObjInstance = static::getInstance();

        // Return the result
        $parametersCnt = count($parameters);
        return match ($parametersCnt) {
            0 => $classObjInstance->$method(),
            1 => $classObjInstance->$method($parameters[0]),
            2 => $classObjInstance->$method($parameters[0], $parameters[1]),
            3 => $classObjInstance->$method($parameters[0], $parameters[1], $parameters[2]),
            4 => $classObjInstance->$method($parameters[0], $parameters[1], $parameters[2], $parameters[3]),
            default => call_user_func_array([$classObjInstance, $method], $parameters),
        };
    }


    /**
     * Returns the class instance.
     *
     * @param boolean $refresh Whether to get a new instance or return the existing one
     * @return static Class instance
     */
    final public static function getInstance(bool $refresh = false): object
    {
        if ($refresh) {
            return static::$instances[static::class] = new static();
        }

        return static::$instances[static::class] ?? static::$instances[static::class] = new static();
    }


    /**
     * Restricts the '__wakeup' magic method.
     */
    final public function __wakeup(): void
    {
    }


    /**
     * Restricts the '__clone' magic method.
     */
    final public function __clone(): void
    {
    }
}
