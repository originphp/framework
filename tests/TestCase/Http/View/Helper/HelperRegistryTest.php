<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Http\View\Helper;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\View\View;
use Origin\Http\Controller\Controller;
use Origin\Http\View\Helper\HelperRegistry;
use Origin\Http\View\Exception\MissingHelperException;

class HelperRegistryTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $controller = new Controller(new Request(), new Response());
        $this->HelperRegistry = new HelperRegistry(new View($controller));
    }

    public function testView()
    {
        $this->assertInstanceOf(View::class, $this->HelperRegistry->view());
    }
    public function testMissingHelper()
    {
        $this->expectException(MissingHelperException::class);
        $this->HelperRegistry->load('Foo');
    }
}
