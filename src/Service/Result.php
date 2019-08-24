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

namespace Origin\Service;

use stdClass;

/**
 * A result was either successful or
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
        $object = $this->toObject($properties);
        foreach (get_object_vars($object) as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Recursively creates an object from an array
     *
     * @param array $data
     * @return void
     */
    private function toObject(array $data) : object
    {
        $obj = new stdClass;
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->toObject($value);
            }
            $obj->{$key} = $value;
        }

        return $obj;
    }
}
