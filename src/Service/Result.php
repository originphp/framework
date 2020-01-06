<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
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
 *  $result = new Result(['success'=>true,'data'=>['foo'=>'bar']]);
 *  $result = new Result(['success'=>false,'error'=>['message'=>'foo does not exist']]);
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
     * Checks if the result was successful, this is done by checking if there is a data property or
     * success property set to true
     *
     * @return boolean
     */
    public function success() : bool
    {
        return ((isset($this->success) and $this->success === true) or isset($this->data));
    }

    /**
     * Checks if the result was an error, this is done by checking if there is a error property or
     * success property set to false
     *
     * @return boolean
     */
    public function error() : bool
    {
        return ((isset($this->success) and $this->success === false) or isset($this->error));
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
    public function toJson(array $options = []) : string
    {
        $options += ['pretty' => false];

        return json_encode($this->toArray(), $options['pretty'] ? JSON_PRETTY_PRINT : 0);
    }

    /**
     * Converts the Result object into an Array
     *
     * @return array
     */
    public function toArray()  : array
    {
        return $this->convertToArray($this);
    }

    /**
     * Recursively converts an object to an array
     *
     * @param object $object
     * @return array
     */
    private function convertToArray(object $object) : array
    {
        if (method_exists($object, 'toArray') and ! $object instanceof Result) {
            return $object->toArray();
        }
        $array = (array) $object;
        foreach ($array as $property => $value) {
            if (is_object($value)) {
                $array[$property] = $this->convertToArray($value);
            }
        }

        return $array;
    }
}
