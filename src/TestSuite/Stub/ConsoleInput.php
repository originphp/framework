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
declare(strict_types=1);
namespace Origin\TestSuite\Stub;

use Origin\Console\ConsoleInput as BaseConsoleInput;
use Origin\TestSuite\Exception\ConsoleInputRequired;

class ConsoleInput extends BaseConsoleInput
{
    private $input = [];

    private $current = -1;

    public function setInput(array $input)
    {
        $this->input = $input;
    }

    public function read(): ?string
    {
        $index = $this->currentIndex();

        if (! isset($this->input[$index])) {
            throw new ConsoleInputRequired('Console input is requesting more input that what was provided');
        }

        return $this->input[$index];
    }

    private function currentIndex(): int
    {
        $this->current ++;

        return $this->current;
    }
}
