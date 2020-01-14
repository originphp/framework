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

namespace Origin\Test\Http\Controller\Exception;

use Origin\Http\Controller\Exception\MissingControllerException;

class MissingControllerExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testException()
    {
        $exception = new MissingControllerException('Widgets');
        $this->assertEquals(404, $exception->getCode());
        $this->assertEquals('WidgetsController class could not be found.', $exception->getMessage());
    }
}
