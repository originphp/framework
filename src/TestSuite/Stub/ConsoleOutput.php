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
declare(strict_types=1);
namespace Origin\TestSuite\Stub;

use Origin\Console\ConsoleOutput as BaseConsoleOutput;

class ConsoleOutput extends BaseConsoleOutput
{
    protected $buffer = '';

    public function write($data, $newLine = true) : int
    {
        $buffer = [];
        foreach ((array) $data as $line) {
            $buffer[] = $line;
        }
        if ($newLine) {
            $buffer[] = '';
        }
        $buffer = implode("\n", $buffer);
        $this->buffer .= $buffer;

        return strlen($buffer);
    }
 
    public function read()
    {
        return $this->buffer;
    }
}

/*
class ConsoleOutput extends BaseConsoleOutput
{
    protected $mode = SELF::RAW;

    protected $buffer = '';

    protected function fwrite(string $data) : int
    {
        $this->buffer .= $data;

        return strlen($data);
    }

    public function read()
    {
        return $this->buffer;
    }
}

*/
