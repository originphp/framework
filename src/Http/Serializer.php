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
namespace Origin\Http;

use Origin\Model\Entity;

class Serializer
{
    /**
     * This actually preps the data for serialization.
     *
     * @param [type] $keys
     * @param [type] $data
     * @return void
     */
    public function serialize($keys, $data)
    {
        if (is_string($keys)) {
            if (isset($data[$keys])) {
                return $this->toArray($data[$keys]);
            }
            return [];
        }
        $result = [];
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $result[$key] = $this->toArray($data[$key]);
            }
        }
        return $result;
    }
    protected function toArray($mixed)
    {
        if (is_object($mixed) and method_exists($mixed, 'toArray')) {
            $mixed = $mixed->toArray();
        }
        return $mixed;
    }
}
