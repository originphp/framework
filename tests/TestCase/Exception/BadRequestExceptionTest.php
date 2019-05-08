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

namespace Origin\Test\Exception;

use Origin\Exception\BadRequestException;

class BadRequestExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testIt()
    {
        $exception = new BadRequestException();
        $this->assertEquals(400, $exception->getCode());
        $this->assertEquals('Bad Request', $exception->getMessage());
    }
}
