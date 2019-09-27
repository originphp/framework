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

namespace Origin\Model\Behavior;

use Origin\Model\Model;
use Origin\Model\Entity;
use Origin\Core\ConfigTrait;
use ArrayObject;

class Behavior
{
    use ConfigTrait;
    
    /**
     * Model for this behavior
     *
     * @var \Origin\Model\Model
     */
    protected $_model = null;

    public function __construct(Model $model, array $config = [])
    {
        $this->_model = $model;

        $this->config($config);
        $this->initialize($config);
    }

    /**
     * Use this so you don't have to overide __construct.
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config) : void
    {
    }

    /**
     * Returns the model
     *
     * @return \Origin\Model\Model
     */
    public function model()
    {
        return $this->_model;
    }
}
