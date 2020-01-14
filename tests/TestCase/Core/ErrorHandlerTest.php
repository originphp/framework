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

namespace Origin\Test\Core;

use Origin\Http\ErrorHandler;
use Origin\Core\Exception\Exception;

class ErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @todo
     */
    public function testIt()
    {
        $stub = $this->createMock(ErrorHandler::class);
        $stub->method('stop');

        $this->assertNull($stub->register());
        $result = $this->render($stub, 'exceptionHandler');
   
        // if stop is mocked, then it does not return text
    }

    protected function render($errorHandler, $method)
    {
        try {
            throw new Exception('foo');
        } catch (Exception $ex) {
        }

        ob_start();

        $errorHandler->{$method}($ex);

        return ob_get_clean();
    }
}
