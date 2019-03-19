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
     * Formats a currency number
     *
     * $number->currency(1024,'USD'); // $1,024
     *
     * @param string|float $value 1234567.43
     * @param string $currency USD|EUR|GBP|AUD etc.
     * @param array $options before,after,places,thousands,decimails
     * @return string
     */
    public function currency($value, string $currency=null, array $options=[])
    {
        return Number::currency($value, $currency, $options);
    }
    /**
    * Formats a percent number
    *
    *  $number->percent(50.55); // 50.55%
    *
    * @param string|float $value 1234567.43
    * @param string $currency USD|EUR|GBP|AUD etc.
    * @param array $options before,after,places,thousands,decimails
    * @return string
    */
    
    public function percent($value, int $precision = 2, $options = [])
    {
        return Number::percent($value, $precision, $options);
    }
    /**
    * Formats a decimal number
    *
    *  $number->decimal(1024.10101010,4); // 1,024.1010
    *
    * @param string|float $value 1234567.43
    * @param string $currency USD|EUR|GBP|AUD etc.
    * @param array $options before,after,places,thousands,decimails
    * @return string
    */
    public function decimal($value, int $precision = 2, $options = [])
    {
        return Number::decimal($value, $precision, $options);
    }
    /**
     * Formats a number
     *
     * $number->format(1024.512); // 1,024.51
     *
     * Options include:
     * before - something to be shown before
     * after - something to be added after
     * thousands - the thousands seperator
     * decimals - the decimals seperator
     * places - how many decimal points to show
     *
     * @param float|string $value
     * @param array $options
     * @return void
     */
    public function format($value, array $options=[])
    {
        return Number::format($value, $options);
    }
}
