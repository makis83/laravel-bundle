<?php

namespace Makis83\LaravelBundle\Traits\api;

use JsonException;
use LogicException;
use Makis83\Helpers\Data;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;

/**
 * Provides methods for returning resource or resource collection data as array.
 * This trait should be used with Laravel resource classes only.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-01-21
 * Time: 17:48
 */
trait ReturnsResourceDataAsArray
{
    /**
     * Return resource or resource collection data as array.
     *
     * @param null|Request|mixed|array|object $request Request data
     * @param mixed $defaultValue Default value
     * @return array|null Resource data array or null if data is not available
     * @throws JsonException if JSON decoding fails
     */
    public function dataToArray(null|Request $request = null, null|array $defaultValue = null): ?array
    {
        // Get request object if it is not provided
        if (null === $request) {
            $request = RequestFacade::capture();
        }

        // Get resource content (JSON)
        $content = $this->toResponse($request)->getContent();
        if (false === $content) {
            return $defaultValue;
        }

        // Decode the resource content
        $data = Data::jsonDecode($content);

        // Check if data is wrapped into a tag
        if (null !== $this::$wrap) {
            return Arr::get($data, $this::$wrap, $defaultValue);
        }

        return $data;
    }
}
