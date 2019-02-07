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

namespace Origin\Test\Core;

use Origin\Core\Log;

class LogTest extends \PHPUnit\Framework\TestCase
{
    public function testWrite()
    {
        $logFilename = LOGS.DS.'log-test.log';
        Log::write('log-test', 'This is a test');
        $expected =  date('Y-m-d G:i:s') ." - This is a test\n";
        $this->assertEquals($expected, file_get_contents($logFilename));
        unlink($logFilename);
    }
}
