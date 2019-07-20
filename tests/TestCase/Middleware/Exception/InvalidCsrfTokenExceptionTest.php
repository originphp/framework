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

namespace Origin\Test\Controller\Exception;

use Origin\Middleware\Exception\InvalidCsrfTokenException;

class InvalidCsrfTokenExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testException()
    {
        $exception = new InvalidCsrfTokenException();
        $this->assertEquals(403, $exception->getCode());
        $this->assertEquals('Invalid CSRF Token', $exception->getMessage());
    }
}
