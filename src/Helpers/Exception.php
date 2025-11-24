<?php

namespace Makis83\LaravelBundle\Helpers;

use Exception as PHPException;
use Safe\Exceptions\PcreException;

/**
 * Exception.php class description.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-11-24
 * Time: 17:48
 */
class Exception
{
    /**
     * @var string METHOD_NOTATION_REGEX Regex to identify method notation in strings
     * @see https://regex101.com/r/8SDeEW/1
     */
    public const string METHOD_NOTATION_REGEX = '/^method:([a-z0-9_]+)$/i';


    /**
     * Return exception message.
     *
     * @param PHPException $exception Exception instance
     * @param array{0: string, 1?: array<string, mixed>}|callable|string|int|float|null $message Exception message
     * @return null|string|int|float Exception message
     */
    public static function message(
        PHPException $exception,
        null|array|callable|string|int|float $message = null
    ): null|string|int|float {
        // Check if message is not defined
        if (null === $message) {
            return $exception->getMessage();
        }

        // Check if message is callable
        if (is_callable($message)) {
            return $message();
        }

        // Check if message is array
        if (is_array($message)) {
            return array_key_exists(1, $message)
                ? __($message[0], $message[1])
                : __($message[0]);
        }

        // Check if message is a method name from the Exception class
        try {
            if (
                is_string($message) &&
                \Safe\preg_match(static::METHOD_NOTATION_REGEX, $message, $matches) &&
                method_exists($exception, $matches[1])
            ) {
                return $exception->{$matches[1]}();
            }
        } catch (PcreException) {
        }

        // Default message
        return $message;
    }


    /**
     * Return exception errors.
     *
     * @param PHPException $exception Exception instance
     * @param array<string>|callable|string|null $errors Exception errors
     * @return array<string> Exception errors
     */
    public static function errors(PHPException $exception, array|callable|string|null $errors = null): array
    {
        // Check if errors are not defined
        if (null === $errors) {
            return [];
        }

        // Check if errors are callable
        if (is_callable($errors)) {
            return $errors();
        }

        // Check if errors is a method name from the Exception class
        try {
            if (
                is_string($errors) &&
                \Safe\preg_match(static::METHOD_NOTATION_REGEX, $errors, $matches) &&
                method_exists($exception, $matches[1])
            ) {
                return $exception->{$matches[1]}();
            }
        } catch (PcreException) {
        }

        // Check if value is string
        if (is_string($errors)) {
            return [$errors];
        }

        // Default case
        return $errors;
    }


    /**
     * Return exception status code.
     *
     * @param PHPException $exception Exception instance
     * @param callable|int|string|null $status Exception status
     * @return int exception status
     */
    public static function status(PHPException $exception, callable|int|string|null $status = null): int
    {
        // Check if status is not defined
        if (null === $status) {
            return 500;
        }

        // Check if status code is callable
        if (is_callable($status)) {
            return $status();
        }

        // Check if message is a method name from the Exception class
        try {
            if (
                is_string($status) &&
                \Safe\preg_match(static::METHOD_NOTATION_REGEX, $status, $matches) &&
                method_exists($exception, $matches[1])
            ) {
                return $exception->{$matches[1]}();
            }
        } catch (PcreException) {
        }

        // Default status
        return (int) $status;
    }
}
