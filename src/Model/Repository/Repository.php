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
namespace Origin\Model\Repository;

use Origin\Model\ModelTrait;
use Origin\Utility\Inflector;

/**
 * Provides the structure for Repository
 */
class Repository
{
    use ModelTrait;
    private $modelClass = null;

    public function __construct()
    {
        if (func_get_args()) {
            /**
             * @deprecated version
             */
            deprecationWarning('Injecting dependencies in Repos has been deprectaed');
            if (method_exists($this, 'initialize')) {
                $this->initialize(...func_get_args());
            }
        }
        
        if ($this->modelClass === null) {
            list($namespace, $class) = namespaceSplit(get_class($this));
            $this->modelClass = Inflector::singular(substr($class, 0, -10));
        }
    }

    /**
     * Lazyload the Model for this Repository
     *
     * @param string $name
     * @return \Origin\Model\Model|
     */
    public function __get($name)
    {
        if ($name === $this->modelClass) {
            return $this->loadModel($name);
        }

        return null;
    }
}
