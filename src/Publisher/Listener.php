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

use Origin\Model\ModelTrait;

class Listener
{
    use ModelTrait;
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * This is called when the listener is created
     *
     * @return void
     */
    public function initialize() : void
    {
    }

    /**
     * This is called before the event method is called on this listener
     *
     * @return void
     */
    public function startup() : void
    {
    }
    /**
     * This is called after the event method is called on this listener
     *
     * @return void
     */
    public function shutdown() : void
    {
    }
}
