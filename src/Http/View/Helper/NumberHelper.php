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
declare(strict_types = 1);
namespace Origin\Http\View\Helper;

use Origin\Utility\Number;

class NumberHelper extends Helper
{
    /**
     * Formats a currency number
     *
     * $number->currency(1024,'USD'); // $1,024
     * $number->currency(1024.00,'USD'); // $1,024.00
     *
     * @param string|float|integer $value 1234567.43
     * @param string $currency USD|EUR|GBP|AUD etc.
     * @param array $options additional options
     * @return string
     */
    public function currency($value, string $currency = null, array $options = []): string
    {
        return Number::currency($value, $currency, $options);
    }
    
    /**
     * Formats a percent number
     *
     *  $number->percent(50.55); // 50.55%
     *
     * @param string|int|float $value 1234567.43
     * @param integer $precision
     * @param array $options additional options
     * @return string
     */
    public function percent($value, int $precision = 2, array $options = []): string
    {
        return Number::percent($value, $precision, $options);
    }

    /**
    * Formats a number with a specified level of precision
    *
    *  $number->precision(1024.10101010,4); // 1,024.1010
    *
    * @param string|int|float $value 1234567.43
    * @param int $precision max number of decimal places to show
    * @param array $options additional options
    * @return string
    */
    public function precision($value, int $precision = 2, array $options = []): string
    {
        return Number::precision($value, $precision, $options);
    }
    /**
     * Formats a number
     *
     * $number->format(1024.512); // 1,024.51
     *
     * @param string|int|float $value
     * @param array $options
     *  - before: something to be shown before
     *  - after: something to be added after
     *  - thousands: the thousands seperator
     *  - decimals: the decimals seperator
     *  - places: how many decimal points to show
     *  - negative: default:()
     * @return string
     */
    public function format($value, array $options = []): string
    {
        return Number::format($value, $options);
    }

    /**
    * Formats bytes to human readable size
    *
    * @param integer $bytes
    * @param array $options
    *   - precision: default:2 the level of precision
    * @return string
    */
    public function readableSize(int $bytes, array $options = []): string
    {
        return Number::readableSize($bytes, $options);
    }
}
