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

namespace Origin\Concern;

use ReflectionMethod;
use ReflectionException;

/**
 * A concern is for sharing code between a Model or Controller without the overhead
 * from calling every single callback (Behavior).
 *
 * A behavior is more a plugin for extending models, a concern is to share code between models.
 *
 *  Do not use Concern to reduce fat models, use Repos instead.
 *
 */
class Concern
{
    /**
     * The object that this concerns
     */
    protected $object = null;

    public function __construct($object, array $config = [])
    {
        $this->object = $object;
        if (method_exists($this, 'initialize')) {
            $this->initialize($config);
        }
    }

    /**
     * Magic for calling
     *
     * @param string $method
     * @param array $arguments
     * @return mixed anything or nothing
     */
    public function __call(string $method, array $arguments)
    {
        if ($this->hasMethod($this->object, $method)) {
            return  call_user_func_array([$this->object,$method], $arguments);
        }
    }

    /**
     * Looks for a concern with the method
     *
     * @param string $method
     * @return mixed
     */
    public function hasMethod($object, string $method) : bool
    {
        $result = false;
       
        try {
            $method = new ReflectionMethod($object, $method);
 
            if ($method->isPublic()) {
                $result = true;
            }
        } catch (ReflectionException $e) {
        }

        return $result;
    }
}
