<?php

namespace Makis83\LaravelBundle\Traits\api;

use Illuminate\Http\Request;
use Makis83\LaravelBundle\Traits\models\UsesCache;

/**
 * Provides methods for working with optional API fields that can be returned in a query.
 * Use this trait in your API controller or a Resource class.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-10-31
 * Time: 19:25
 */
trait ProcessesApiExtraFields
{
    use UsesCache;

    /**
     * @var null|string[] $extraFields array of extra field names
     */
    private static ?array $extraFields = null;

    /**
     * @var string $extraFieldsFieldName name of query parameter
     */
    public string $extraFieldsFieldName = 'expand';


    /**
     * Populate extra fields.
     * @param Request $request Request object
     * @param string[] $fields Additional fields that can be manually added to the list
     * @return void
     */
    private function populateExtraFields(Request $request, array $fields = []): void
    {
        // Populate extra fields array if it is not defined (null)
        if (null === self::$extraFields) {
            // Check if 'expand' parameter exists in a query
            if ($request->has($this->extraFieldsFieldName)) {
                // Skip if 'expand' parameter is not a string
                $extraFieldsParamValue = $request->query($this->extraFieldsFieldName);
                if (!is_string($extraFieldsParamValue) || ('' === trim($extraFieldsParamValue))) {
                    self::$extraFields = $this->sortExtraFields($fields);
                    return;
                }

                // Get array with extra fields
                $extraFieldsResult = [];
                $extraFieldsTmpArray = explode(',', $extraFieldsParamValue);
                foreach ($extraFieldsTmpArray as $field) {
                    $trimmedValue = trim($field);
                    if ('' !== $trimmedValue) {
                        $extraFieldsResult[] = $trimmedValue;
                    }
                }

                // Add additional fields to array
                if (!empty($fields)) {
                    $extraFieldsResult += $fields;
                }

                // Sort values
                self::$extraFields = $this->sortExtraFields($extraFieldsResult);
                return;
            }

            // Just add additional fields to array
            self::$extraFields = $this->sortExtraFields($fields);
        }
    }


    /**
     * Sort extra fields.
     * @param string[] $fields fields that should be sorted
     * @return array sorted fields
     */
    private function sortExtraFields(array $fields): array
    {
        // Return empty array if no fields are provided
        if (empty($fields)) {
            return [];
        }

        // Make values unique
        $extraFieldsResult = array_keys(array_flip($fields));

        // Sort values
        sort($extraFieldsResult);

        // Result
        return $extraFieldsResult;
    }


    /**
     * Return an array of extra fields (optional fields that can be returned in a query).
     * @param Request $request request object
     * @param string[] $fields additional fields that can be added to the list
     * @return array extra fields
     */
    public function extraFields(Request $request, array $fields = []): array
    {
        $this->populateExtraFields($request, $fields);
        return self::$extraFields;
    }
}
