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
     * The original idea was for this to serialize, but it now
     * only converts the data to array
     *
     * @param string|array $keys
     * @param array $data
     * @return array
     */
    public function serialize($keys, array $data) : array
    {
        $result = [];
        if (is_string($keys)) {
            if (isset($data[$keys])) {
                $result = $this->toArray($data[$keys]);
            }
            return $result;
        }
       
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
