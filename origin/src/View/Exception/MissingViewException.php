<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\View\Exception;

use Origin\Exception\Exception;

class MissingViewException extends Exception
{
    protected $template = 'View for %sController action %s could not be found.';
    protected $defaultErrorCode = 404;
}
