<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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

trait InitializerTrait
{
    /**
     * Initializes Traits where their is a method initializeTraitNameWithoutTraitSuffix
     *
     * Example
     *
     *   trait DeletableTrait
     *   {
     *      protected function intializeDeleteable() : void
     *      {
     *
     *      }
     *    }
     *
     *  in the construct of the class you call `initializeTraits`
     *
     * @return void
     */
    private function initializeTraits() : void
    {
        $args = func_get_args() ?? [];
        foreach ($this->classUses() as $trait) {
            list($namespace, $className) = namespaceSplit($trait);
          
            $method = $className;
            if (strpos($className, 'Trait') !== false) {
                $method = substr($className, 0, -5);
            }

            $method = 'initialize' . $method;
            if (method_exists($this, $method)) {
                $this->$method(...$args);
            }
        }
    }

    /**
     * Gets the traits used by this class
     *
     * @return array
     */
    private function classUses() : array
    {
        $class = $this;
     
        $traits = [];
        while ($class) {
            $traits = array_merge(class_uses($class), $traits);
            $class = get_parent_class($class);
        }
    
        return array_unique($traits);
    }
}
