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
declare(strict_types=1);

namespace Origin\Job\Model;

use Origin\Model\Model;
use Origin\Model\Concern\Timestampable;

/**
 * Internal model which is used by the Database engine, do not call directly.
 */
class Queue extends Model
{
    use Timestampable;
}
