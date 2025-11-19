<?php

namespace Makis83\LaravelBundle\Exceptions;

use Throwable;
use RuntimeException;

/**
 * Exception thrown when a trait is used in an invalid context.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-10-22
 * Time: 17:17
 */
class InvalidUsageException extends RuntimeException
{
    /**
     * @var string The trait that was used incorrectly
     */
    protected string $trait;

    /**
     * @var string The class that used the trait incorrectly
     */
    protected string $class;

    /**
     * @var string The expected parent class or interface
     */
    protected string $expected;


    /**
     * Constructor for InvalidUsageException.
     *
     * @param string $class The class that used the trait incorrectly
     * @param string $trait The trait that was used incorrectly
     * @param string $expected The expected parent class or interface
     * @param int $code Exception code
     * @param Throwable|null $previous Previously thrown exception
     */
    public function __construct(
        string $class,
        string $trait,
        string $expected,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            'The trait %s can only be used in classes that extend/implement %s. %s does not extend/implement %s.',
            $trait,
            $expected,
            $class,
            $expected
        );

        parent::__construct($message, $code, $previous);

        $this->trait = $trait;
        $this->class = $class;
        $this->expected = $expected;
    }


    /**
     * Get the trait that was used incorrectly.
     *
     * @return string Trait name
     */
    public function getTrait(): string
    {
        return $this->trait;
    }


    /**
     * Get the class that used the trait incorrectly.
     *
     * @return string Class name
     */
    public function getClass(): string
    {
        return $this->class;
    }


    /**
     * Get the expected parent class or interface.
     *
     * @return string Expected parent class or interface
     */
    public function getExpected(): string
    {
        return $this->expected;
    }
}
