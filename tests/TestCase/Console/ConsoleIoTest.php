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

namespace Origin\Test\Console\ConsoleIo;

use Origin\Console\ConsoleIo;
use Origin\TestSuite\Stub\ConsoleOutput;



class MockConsoleIo extends ConsoleIo
{
    public function getContents(){
        return $this->stdin->read();
    }
}

class ConsoleIoTest extends \PHPUnit\Framework\TestCase
{

    public function getConsoleIo(){
        $consoleOutput = new ConsoleOutput('php://memory');
        return new ConsoleIo( $consoleOutput, $consoleOutput);
    }
   

}
