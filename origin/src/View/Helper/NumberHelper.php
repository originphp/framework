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

namespace Origin\View\Helper;

use Origin\Utils\Number;

class NumberHelper extends Helper
{
    public function currency(float $value, string $currency = null, array $options = [])
    {
        return Number::currency($value, $currency, $options);
    }

    public function format(float $value, array $options = [])
    {
        return Number::format($value, $options);
    }

    public function precision(float $value, int $precision = 2, array $options = [])
    {
        return Number::currency($value, $precision, $options);
    }

    public function toPercentage(float $value, int $precision = 2, array $options = [])
    {
        return Number::currency($value, $precision, $options);
    }
}
