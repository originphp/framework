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

use Origin\I18n\I18n;

if (! function_exists('__')) {
    /**
     * Translate and format a string.
     *
     * @example __('Order with id {id} by user {name}...',['id'=>$user->id,'name'=>$user->name]);
     * @param string $string
     * @param array $vars array of vars e.g ['id'=>$user->id,'name'=>$user->name]
     * @return string|null formatted
     */
    function __(string $string = null, array $vars = []): ?string
    {
        if ($string) {
            return I18n::translate($string, $vars);
        }

        return null;
    }
}
