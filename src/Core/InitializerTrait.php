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

use ReflectionMethod;
use ReflectionException;

trait InitializerTrait
{
    /**
     * Initializes Traits where their is a method initializeTraitNameWithoutTraitSuffix
     *
     * Example
     *
     *   trait DeletableTrait
     *   {
     *      protected function intializeDeleteable()
     *      {
     *
     *      }
     *    }
     *
     *  in the construct you call this method
     *
     * @return void
     */
    private function initializeTraits() : void
    {
        $class = $this;
     
        $traits = [];
        while ($class) {
            $traits = array_merge(class_uses($class), $traits);
            $class = get_parent_class($class);
        }
    
        $traits = array_unique($traits);

        $args = func_get_args() ?? [];
        foreach ($traits as $trait) {
            list($namespace, $className) = namespaceSplit($trait);
          
            $method = $className;
            if (strpos($className, 'Trait') !== false) {
                $method = substr($className, 0, -5);
            }

            $method = 'initialize' . $method;
            if ($this->hasMethod($this, $method)) {
                $this->$method(...$args);
            }
        }
    }

    /**
     * Checks if the object has a method
     *
     * @param object $object
     * @param string $method
     * @return boolean
     */
    private function hasMethod(object $object, string $method) : bool
    {
        try {
            $method = new ReflectionMethod($object, $method);

            return ($method->isPublic() or $method->isProtected());
        } catch (ReflectionException $e) {
        }

        return false;
    }
}
