<?php

namespace Makis83\LaravelBundle\Traits;

use Illuminate\Support\Str;
use RuntimeException as PHPRuntimeException;

/**
 * Provides dynamic property and method access.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-11-08
 * Time: 19:25
 */
trait UsesDynamicProperties
{
    /**
     * Get the value of a property or return value of a method with the same name.
     *
     * @param string $name The name of the property or method
     * @return mixed The value of the property or the return value of the method
     * @throws PHPRuntimeException If the property or method does not exist
     */
    final public function __get(string $name): mixed
    {
        // Check if the method exists
        $methodName = Str::camel($name);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        // Check if the property exists
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        // If neither property nor method exists, return the exception
        throw new PHPRuntimeException('Unknown property or method: ' . $name);
    }


    /**
     * Set the value of a property.
     *
     * @param string $name The name of the property
     * @param mixed $value The value to be set
     * @return void
     */
    final public function __set(string $name, mixed $value): void
    {
        // Only set the value if the property exists
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }


    /**
     * Check if a property is set.
     *
     * @param string $name The name of the property
     * @return bool True if the property is set, otherwise false
     */
    final public function __isset(string $name): bool
    {
        // Check if the property has a getter
        if (method_exists($this, $name)) {
            return $this->__get($name) !== null;
        }

        return false;
    }


    /**
     * Retrieve the value of a property or the result of a method with the same name.
     *
     * @param string $name The name of the property or method to retrieve
     * @return mixed The value of the property or method
     * @throws PHPRuntimeException If the property or method does not exist
     */
    final public function getProperty(string $name): mixed
    {
        // Check if a method with the property name exists and call it if so
        $methodName = Str::camel($name);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        // Check if a property with the given name exists and return its value if so
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new PHPRuntimeException('Unknown property or method: ' . $name);
    }
}
