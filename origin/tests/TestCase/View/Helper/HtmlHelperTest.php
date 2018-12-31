<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\View\Helper;

use Origin\View\Helper\HtmlHelper;
use Origin\View\View;
use Origin\Controller\Controller;
use Origin\Controller\Request;

class ArticlesController extends Controller
{
}
class MockHtmlHelper extends HtmlHelper
{
}
class HtmlHelperTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $request = new Request('articles/edit/2048');
        $controller = new ArticlesController($request);
        $View = new View($controller);
        $this->HtmlHelper = new MockHtmlHelper($View);
    }

    public function testLink()
    {
        $expected = '<a href="/">view</a>';
        $result = $this->HtmlHelper->link('view', null);
        $this->assertEquals($expected, $result);

        $expected = '<a href="/articles/view/1024">view</a>';
        $result = $this->HtmlHelper->link('view', ['action' => 'view', 1024]);
        $this->assertEquals($expected, $result);

        $expected = '<a href="/articles/view/2048" class="custom">view</a>';
        $result = $this->HtmlHelper->link('view', ['action' => 'view', 2048], ['class' => 'custom']);
        $this->assertEquals($expected, $result);
    }
}
