<?php

namespace Makis83\LaravelBundle\Exceptions;

use Exception;
use Throwable;
use JsonException;
use Makis83\Helpers\Data;

/**
 * Extended exception.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-09-17
 * Time: 17:04
 */
class ExtendedException extends Exception
{
    /**
     * @var int OPTIONS JSON-encode options
     */
    public const int OPTIONS =
        JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE;

    /**
     * @var int $status status code
     */
    protected int $status;

    /**
     * @var string|null $messageText Text of exception message.<br>
     * ```$this->getMessage()``` now returns a JSON encoded string with message and additional data
     */
    protected null|string $messageText = null;

    /**
     * @var array<int|string, mixed> $data Additional data
     */
    protected array $data = [];


    /**
     * Json encodes the message and calls the parent constructor.
     *
     * @param int $status HTTP status code
     * @param null|string $message Exception message
     * @param array<int|string, mixed> $data Exception data
     * @param int $code Exception code
     * @param Throwable|null $previous Previously thrown exception
     * @throws JsonException if JSON encoding fails
     */
    public function __construct(
        int $status,
        ?string $message = null,
        array $data = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        // Run parent exception
        parent::__construct(
            Data::jsonEncode(['status' => $status, 'message' => $message, 'data' => $data], self::OPTIONS),
            $code,
            $previous
        );

        // Set vars
        $this->status = $status;
        $this->messageText = $message;
        $this->data = $data;
    }


    /**
     * Returns HTTP status code.
     *
     * @return int status code
     */
    public function getStatus(): int
    {
        return $this->status;
    }


    /**
     * Returns an exception message.
     *
     * Similar to ```getMessage()```, but while ```getMessage()``` returns JSON-encoded string with message and data,
     * this will return the message only in a text format.
     * @return null|string exception info
     */
    public function getMessageText(): ?string
    {
        return $this->messageText;
    }


    /**
     * Returns an array of additional data.
     *
     * @return array<int|string, mixed> additional data
     */
    public function getData(): array
    {
        return $this->data;
    }
}
