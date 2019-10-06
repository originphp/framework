<?php
declare(strict_types = 1);
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
        if ($this->modelClass === null) {
            list($namespace, $class) = namespaceSplit(get_class($this));
            $this->modelClass = Inflector::singular(substr($class, 0, -10));
        }
        $this->initialize();
    }

    public function initialize() : void
    {
    }
    /**
     * Lazyload the Model for this Repository
     *
     * @param string $name
     * @return \Origin\Model\Model|null
     */
    public function __get($name)
    {
        return $name === $this->modelClass ? $this->loadModel($name) : null;
    }
}
