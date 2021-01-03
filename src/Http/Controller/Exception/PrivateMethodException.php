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

namespace Origin\Http\Controller\Exception;

use Origin\Core\Exception\Exception;

class PrivateMethodException extends Exception
{
    protected $template = '%sController action %s is private or protected.';
    protected $defaultErrorCode = 404;
}
