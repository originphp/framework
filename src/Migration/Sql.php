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
declare(strict_types = 1);
namespace Origin\Migration;

class Sql
{
    /**
     * Holds a statement or group of statements
     *
     * @var array
     */
    private $statements = [];
    
    public function __construct($mixed)
    {
        $this->statements = array_filter((array) $mixed);
    }

    /**
     * Gets the statements for this
     *
     * @return array
     */
    public function statements(): array
    {
        return $this->statements;
    }
}
