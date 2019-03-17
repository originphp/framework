<?php
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

namespace Origin\View\Helper;

use Origin\Utility\Number;

class NumberHelper extends Helper
{

    /**
     * Formats a number into a currency.
     *
     * @param float  $value
     * @param string $currency EUR
     * @param array  $options  precision|places|before|after|pattern
     *
     * @return string result $1,234.56
     */
    public function currency(float $value, string $currency = null, array $options = [])
    {
        return Number::currency($value, $currency, $options);
    }
    /**
     * Formats a number. This is used by other functions.
     *
     * @param float $value
     * @param array $options precision|places|before|after|pattern
     *
     * @return string 1,234.56
     */
    public function format(float $value, array $options = [])
    {
        return Number::format($value, $options);
    }
    /**
     * Formats a floating point number.
     *
     * @param float $value
     * @param int   $precision number of decimal places
     * @param array $options   places|before|after|pattern
     *
     * @return string 1234.56
     */
    public function decimal(float $value, int $precision = 2, array $options = [])
    {
        return Number::decimal($value, $precision, $options);
    }

    /**
     * Formats a number to a percentage.
     *
     * @param float $value
     * @param int   $precision number of decimal places
     * @param array $options   places|before|after|pattern|multiply
     *
     * @return string 75.00%
     */
    public function percent(float $value, int $precision = 2, array $options = [])
    {
        return Number::percent($value, $precision, $options);
    }
}
