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
namespace Origin\Core;

trait InitializerTrait
{

    /**
     * Initializes Traits where their is a method with the name of the Trait.
     *
     * Example
     *
     *   trait DeletableTrait
     *   {
     *      public function deleteable(){
     *
     *      }
     *    }
     *
     *  in the construct you call this method
     *
     * @return void
     */
    private function initializeTraits()
    {
        $class = $this;
        $traits = [];
        while ($class) {
            $traits = array_merge(class_uses($class), $traits);
            $class = get_parent_class($class);
        }
        
        $traits = array_unique($traits);

        foreach ($traits as $trait) {
            list($namespace, $className) = namespaceSplit($trait);
            $method = substr($className, 0, -5);
            if (method_exists($this, $method)) {
                $this->$method(...func_get_args());
            }
        }
    }
}
