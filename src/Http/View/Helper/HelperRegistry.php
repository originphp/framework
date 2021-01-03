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
namespace Origin\Http\View\Helper;

use Origin\Core\Resolver;
use Origin\Http\View\View;
use Origin\Core\ObjectRegistry;
use Origin\Http\View\Exception\MissingHelperException;

class HelperRegistry extends ObjectRegistry
{
    /**
     * Holds the view object
     *
     * @var \Origin\Http\View\View
     */
    protected $view = null;

    /**
     * Constructor
     *
     * @param \Origin\Http\View\View $view
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * Resolves the class name of a helper
     *
     * @param string $class
     * @return string|null $namespacedClass
     */
    protected function className(string $class): ?string
    {
        return Resolver::className($class, 'View/Helper', null, 'Http');
    }

    /**
     * Creates the object
     *
     * @param string $class
     * @param array $options
     * @return \Origin\Http\View\Helper\Helper
     */
    protected function createObject(string $class, array $options = []): Helper
    {
        return new $class($this->view, $options);
    }

    /**
     * Throws an exception
     *
     * @param string $object
     * @return void
     */
    protected function throwException(string $object): void
    {
        throw new MissingHelperException($object);
    }

    /**
     * Returns a view object
     *
     * @return View
     */
    public function view(): View
    {
        return $this->view;
    }
}
