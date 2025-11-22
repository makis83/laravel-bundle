<?php

namespace Makis83\LaravelBundle\Traits;

/**
 * Adds possibility to manipulate with model request attributes.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-09-18
 * Time: 12:37
 */
trait HasAttributes
{
    use UsesSingleton;


    /**
     * Returns attribute description.
     *
     * @param string $name Attribute name
     * @param null|string $default Default value
     * @return string Attribute description
     */
    public static function attributeDescription(string $name, ?string $default = null): string
    {
        // Get class object
        $classObj = static::getInstance();

        // Return default attribute name if method does not exist
        if (!method_exists($classObj, 'attributes')) {
            return $default ?: $name;
        }

        // Get attributes
        $attributes = $classObj->attributes();

        // Check if given attribute is present is the attributes array
        if (array_key_exists($name, $attributes)) {
            return $attributes[$name];
        }

        // Return default value
        return $default ?: $name;
    }


    /**
     * Returns default attribute value for model.
     *
     * @param string $attribute Attribute
     * @param mixed $default Default value
     * @return mixed Attribute default value
     */
    public static function attributeDefaultValue(string $attribute, mixed $default = null): mixed
    {
        $attributes = static::attributesDefault();
        return array_key_exists($attribute, $attributes) ? $attributes[$attribute] : $default;
    }


    /**
     * Returns default attributes for model.
     *
     * @return array<string, mixed> Default attributes
     */
    public static function attributesDefault(): array
    {
        // Get class object
        $classObj = static::getInstance();

        // Check if property exists
        if (property_exists($classObj, 'attributes') && is_array($classObj->attributes)) {
            return $classObj->attributes;
        }

        // return empty array
        return [];
    }
}
