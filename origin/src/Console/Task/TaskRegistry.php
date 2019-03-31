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

namespace Origin\Console\Task;

use Origin\Console\Shell;
use Origin\Core\ObjectRegistry;
use Origin\Core\Resolver;
use Origin\Console\Exception\MissingTaskException;

/**
 * A quick and easy way to create models and add them to registry. Not sure if
 * this will be added.
 */
class TaskRegistry extends ObjectRegistry
{
    /**
     * Injected Shell object
     *
     * @var \Origin\Console\Shell
     */
    protected $shell = null;

    public function __construct(Shell $shell)
    {
        $this->shell = $shell;
    }

    protected function className(string $class)
    {
        return Resolver::className($class, 'Console/Task');
    }

    /**
     * Undocumented function
     *
     * @param string $class
     * @param array $options
     * @return \Origin\Console\Task\Task
     */
    protected function createObject(string $class, array $options = [])
    {
        return new $class($this->shell, $options);
    }

    protected function throwException(string $object)
    {
        throw new MissingTaskException($object);
    }

    /**
     * Undocumented function
     *
     * @return \Origin\Console\Shell
     */
    public function shell()
    {
        return $this->shell;
    }
}
