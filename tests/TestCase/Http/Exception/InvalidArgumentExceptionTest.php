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

use Origin\Core\Exception\InvalidArgumentException;

class InvalidArgumentExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testException()
    {
        $exception = new InvalidArgumentException();
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals('Invalid Argument', $exception->getMessage());
    }
}
