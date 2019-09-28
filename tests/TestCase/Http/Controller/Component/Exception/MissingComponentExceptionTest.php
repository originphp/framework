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

namespace Origin\Test\Http\Controller\Component\Exception;

use Origin\Http\Controller\Component\Exception\MissingComponentException;

class MissingComponentExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testIt()
    {
        $exception = new MissingComponentException('MathComponent');
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals('MathComponent could not be found.', $exception->getMessage());
    }
}
