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

namespace Origin\View;

use Origin\Exception\Exception;

/**
 * This will also be used by Email,.
 */
class Templater
{
    /**
     * Wether to allow empty data.
     *
     * @var bool
     */
    public $allowBlanks = true;

    public function format(string $template, array $data)
    {
        foreach ($data as $key => $value) {
            if ($this->allowBlanks === false and ($value === '' or $value === null)) {
                throw new Exception(sprintf('Empty data for %s', $key));
            }
            $template = str_replace("{{$key}}", $value, $template);
        }
        if (preg_match_all('/\{([^ }]+)\}/', $template, $matches, PREG_PATTERN_ORDER)) {
            throw new Exception(sprintf('Some fields in templates not matched'));
        }

        return $template;
    }
}
