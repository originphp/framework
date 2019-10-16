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
namespace Origin\Core;

/**
 * The Cache library has been decoupled, so this library offers a ultra fast caching for arrays and objects
 */
class Cache
{
    /**
     * Internal caching for array and objects. By default it uses PHP serialize method. Even without
     * serialization you can store arrays and/or stdclass objects
     *
     * @param string $key
     * @param mixed $data
     * @param array $options
     *   - serialize: serializes data using PHP serialize method.
     *   - duration: 3600 seconds which it will be valid
     * @return boolean
     */
    public static function set(string $key, $data, array $options = []): bool
    {
        $options += ['serialize' => true, 'duration' => 3600];

        if (! ctype_alnum(str_replace(['-', '_'], '', $key))) {
            throw new InvalidArgumentException('Invalid cache key');
        }

        if ($options['serialize']) {
            $data = serialize($data);
        }

        $expires = time() + $options['duration'];
        $data = var_export([$expires, $data, $options['serialize']], true);

        /**
         * Handle Stdclass objects and arrays of Stdclass objects e.g. (obj) $array
         */
        if ($options['serialize'] === false) {
            $data = str_replace('stdClass::__set_state', '(object)', $data);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'cache');
        $contents = '<?php $data = ' . $data . ';';

        return file_put_contents($tmp, $contents, LOCK_EX) and rename($tmp, CACHE . DS . $key);
    }

    /**
     * Gets an item from internal cache
     *
     * Cache data is stored like this [int $expires,mixed $data,bool $serialized]
     *
     * @param string $key
     * @return mixed
     */
    public static function cache_get(string $key)
    {
        if (! ctype_alnum(str_replace(['-', '_'], '', $key))) {
            throw new InvalidArgumentException('Invalid cache key');
        }

        @include  CACHE . DS . $key;
        if (isset($data) and $data[0] > time()) {
            return $data[2] ? unserialize($data[1]) : $data[1];
        }

        return null;
    }
}
