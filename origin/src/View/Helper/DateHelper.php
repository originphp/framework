<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\View\Helper;

use Origin\Utils\Date;

class DateHelper extends Helper
{
    public function format($dateString)
    {
        return Date::format($dateString);
    }
}
