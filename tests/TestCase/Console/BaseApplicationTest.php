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

namespace Origin\Test\Console;

use Origin\Console\BaseApplication;

class BaseApplicationTest extends \PHPUnit\Framework\TestCase
{
    public function testDispatch()
    {
        // Call the TestCommand::empty
        $exitCode = (new BaseApplication())->dispatch(['/BaseApplicationTest.php','test','empty']);
        $this->assertEquals(0, $exitCode);
    }
}
