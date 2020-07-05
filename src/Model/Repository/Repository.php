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
namespace Origin\Model\Repository;

use Origin\Core\HookTrait;
use Origin\Core\ModelTrait;
use Origin\Inflector\Inflector;

/**
 * Provides the structure for Repository
 */
class Repository
{
    use ModelTrait, HookTrait;
    
    /**
     * Model class name
     *
     * @var string
     */
    private $modelClass = null;

    /**
     * Constructor. Any arguments passed will be sent to the initialize method.
     */
    public function __construct()
    {
        if ($this->modelClass === null) {
            list($namespace, $class) = namespaceSplit(get_class($this));
            $this->modelClass = Inflector::singular(substr($class, 0, -10));
        }
        /**
         * Models are dependcies and should not be lazyloaded.
         */
        $this->loadModel($this->modelClass);
        $this->executeHook('initialize', func_get_args());
    }
}
