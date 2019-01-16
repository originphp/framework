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

namespace Origin\Test\Controller\Component;

use Origin\Controller\Component\PaginatorComponent;
use Origin\TestSuite\TestTrait; // callMethod + getProperty
use Origin\TestSuite\OriginTestCase;
use Origin\Controller\Controller;
use Origin\Controller\Request;
use Origin\Controller\Response;

class PaginatorControllerTest extends Controller
{
    use TestTrait;
}

class MockPaginatorComponent extends PaginatorComponent
{
    use TestTrait;
}

class PaginatorComponentTest extends OriginTestCase
{
    public $fixtures = ['Framework.Article'];

    public function setUp()
    {
        parent::setUp();

        $Request = new Request('controller_posts/index');
        $Response = new Response();
        $this->Controller = new PaginatorControllerTest($Request, $Response);
        $this->PaginatorComponent = new PaginatorComponent($this->Controller);
    }

    public function testPaginate()
    {
        $this->assertTrue(true);
    }

    public function test2()
    {
        $this->assertTrue(true);
    }

    public function test3()
    {
        $this->assertTrue(true);
    }
}
