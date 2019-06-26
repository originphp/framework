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

namespace Origin\Test\View;

use Origin\View\Helper\Helper;
use Origin\View\View;
use Origin\Http\Response;
use Origin\Http\Request;
use Origin\Controller\Controller;

class HelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $controller = new Controller(new Request(), new Response());
        $this->Helper = new Helper(new View($controller));
    }
    public function testRequest()
    {
        $this->assertInstanceOf(Request::class, $this->Helper->request());
    }
    public function testResponse()
    {
        $this->assertInstanceOf(Response::class, $this->Helper->response());
    }
    public function testGet()
    {
        $this->assertNull($this->Helper->foo);
    }
}
