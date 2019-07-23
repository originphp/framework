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

namespace Origin\Test\View\Helper;

use Origin\View\View;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Controller\Controller;
use Origin\View\Helper\HelperRegistry;
use Origin\View\Exception\MissingHelperException;

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
