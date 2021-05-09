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
namespace Origin\TestSuite;

trait TestTrait
{
    /**
     * Invoke protected/private methods.
     *
     * @param object $object this, class object
     * @param string $method doSomething
     * @param array  $args   [arg1,arg2]
     *
     * @return mixed $result from function
     */
    public function callMethod(string $method, array $args = [])
    {
        if (empty($args)) {
            return $this->$method();
        }

        return call_user_func_array([$this, $method], $args);
    }

    public function getProperty(string $property)
    {
        if (isset($this->$property)) {
            return $this->$property;
        }
    }

    public function setProperty(string $property, $value)
    {
        $this->$property = $value;
    }
}
