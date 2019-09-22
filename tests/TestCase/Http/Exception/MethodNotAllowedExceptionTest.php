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

namespace Origin\Test\Http\Exception;

use Origin\Http\Exception\MethodNotAllowedException;

class MethodNotAllowedExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testException()
    {
        $exception = new MethodNotAllowedException();
        $this->assertEquals(405, $exception->getCode());
        $this->assertEquals('Method Not Allowed', $exception->getMessage());
    }
}
