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
namespace Origin\Publisher;

use \Origin\Core\EventDispatcher;
use Origin\Model\ModelTrait;

class Listener
{
    use ModelTrait,EventDispatcher;

    public function __construct()
    {
        $this->dispatchEvent('initialize');
    }

    /**
     * Dispatches a method
     *
     * @param string $method
     * @param array $arguments
     * @return boolean
     */
    public function dispatch(string $method,array $arguments = []) : bool
    {
        $this->dispatchEvent('startup');
        if($this->dispatchEvent($method, $arguments) === false){
            return false;
        }
        $this->dispatchEvent('shutdown');
        return true;
    }
}
