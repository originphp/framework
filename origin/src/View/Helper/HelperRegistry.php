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

namespace Origin\View\Helper;

use Origin\Core\ObjectRegistry;
use Origin\View\View;
use Origin\Core\Resolver;
use Origin\View\Exception\MissingHelperException;

/**
 * A quick and easy way to create models and add them to registry. Not sure if
 * this will be added.
 */
class HelperRegistry extends ObjectRegistry
{
    protected $view = null;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    protected function className(string $class)
    {
        return Resolver::className($class, 'View/Helper');
    }

    /**
     * Creates the object
     *
     * @param string $class
     * @param array $options
     * @return \App\View\Helper\Helper
     */
    protected function createObject(string $class, array $options = [])
    {
        return new $class($this->view, $options);
    }

    protected function throwException(string $object)
    {
        throw new MissingHelperException($object);
    }

    public function view()
    {
        return $this->view;
    }
}
