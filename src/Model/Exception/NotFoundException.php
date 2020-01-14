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

namespace Origin\Model\Exception;

use Origin\Core\Exception\Exception;

/**
 * @deprecated This will be deprecated in future major release, this is
 * kept for backwards comptability
 */
class NotFoundException extends Exception
{
    protected $defaultErrorCode = 404;
}
