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

namespace Origin\Concern;

/**
 * A concern is for sharing code between a Model or Controller without the overhead
 * from calling every single callback (Behavior).
 *
 * A behavior is more a plugin for extending models, a concern is to share code between models.
 *
 *  Do not use Concern to reduce fat models, use Repos instead.
 *
 */
use Origin\Controller\Controller;

class ControllerConcern extends Concern
{
    /**
     * The object that this concerns
     *
     * @var \Origin\Controller\Controller
     */
    protected $object = null;
    /**
     * Returns the controller
     *
     * @return \Origin\Controller\Controller
     */
    public function controller() : Controller
    {
        return $this->object;
    }
}
