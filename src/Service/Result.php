<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Service;

/**
 * The Result object is handling results from Service Objects. Typically you will use multiple services
 * in an app, and those services an be shared accross apps. So you want to decide upon a style to use to
 * prevent. The docs suggest a design based upon the Google JSON style guide, this means on success there
 * is a data key and on an error there is an error key.
 *
 *  # Examples
 *
 *  $result = new Result(['data'=>['foo'=>'bar']]);
 *  $result = new Result(['error'=>['message'=>'foo does not exist']]);
 *
 *  You don't have to set the success key, you can just use `data` OR `error `inline with the gle JSON style guide
 *  the success and error methods will pick this up. For example:
 *
 *  $result = new Result([data'=>['foo'=>'bar']]);
 *  $result = new Result(['error'=>['message'=>'foo does not exist']]);
 *
 *  if($result->success()) {}
 *
 */
class Result
{
    /**
     * Constructor
     *
     * @param array $properties
     */
    public function __construct(array $properties)
    {
        foreach ($properties as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Checks if the result is a success, does not have error key
     *
     * @return boolean
     */
    public function success(): bool
    {
        return ! isset($this->error);
    }

    /**
     * Gets the data from the result, or specific data using the key
     *
     * @param string $key
     * @return mixed
     */
    public function data(string $key = null)
    {
        $data = isset($this->data) ? $this->data : null;

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? null;
    }

    /**
     * Gets the error array or a value for a particular key
     *
     * @param string $key
     * @return mixed
     */
    public function error(string $key = null)
    {
        $data = isset($this->error) ? $this->error : null;

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? null;
    }

    /**
    * Magic method is trigged when the object is treated as string.
    *
    * @return string
    */
    public function __toString()
    {
        return $this->toJson(['pretty' => true]);
    }

    /**
     * Converts the result object to JSON
     *
     * @param array $options
     * @return string
     */
    public function toJson(array $options = []): string
    {
        $options += ['pretty' => false];

        return json_encode($this->toArray(), $options['pretty'] ? JSON_PRETTY_PRINT : 0);
    }

    /**
     * Converts the Result object into an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->convertToArray(get_object_vars($this));
    }

    /**
     * Recursive function for toArray
     *
     * @param array
     * @return array
     */
    private function convertToArray(array $data): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->convertToArray($value);
            } elseif (is_object($value)) {
                $value = method_exists($value, 'toArray') ? $value->toArray() : (array) $value;
            }

            $out[$key] = $value;
        }

        return $out;
    }
}
