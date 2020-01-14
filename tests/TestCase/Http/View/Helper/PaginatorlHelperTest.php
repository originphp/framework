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

namespace Origin\Test\Http\View\Helper;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\View\View;
use Origin\Http\Controller\Controller;
use Origin\Http\View\Helper\PaginatorHelper;

class PostsController extends Controller
{
}
class MockPaginatorHelper extends PaginatorHelper
{
}
class PaginatorHelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $request = new Request('articles/edit/2048');
        $controller = new PostsController($request, new Response());
        $View = new View($controller);
        $this->PaginatorHelper = new MockPaginatorHelper($View);
    }

    public function testSort()
    {
        $Paginator = $this->PaginatorHelper;

        $expected = '<a href="/articles/edit/2048?sort=id&direction=asc">Id</a>';
        $result = $Paginator->sort('id');
        $this->assertEquals($expected, $result);
    }

    public function testSortAsc()
    {
        $request = new Request('articles/edit/2048?sort=id&direction=asc');
        $controller = new PostsController($request, new Response());
        $View = new View($controller);
        $Paginator = new MockPaginatorHelper($View);

        $paging = ['current' => 1, 'pages' => 1,  'records' => 10, 'sort' => 'id', 'direction' => 'asc', 'prevPage' => false, 'nextPage' => false];
        $Paginator->view()->set('paging', $paging);

        $expected = '<a href="/articles/edit/2048?sort=id&direction=desc" class="asc">Id</a>';
        $result = $Paginator->sort('id');
        $this->assertEquals($expected, $result);

        $expected = '<a href="/articles/edit/2048?sort=name&direction=asc">Name</a>';
        $result = $Paginator->sort('name');
        $this->assertEquals($expected, $result);
    }

    public function testSortDesc()
    {
        $request = new Request('articles/edit/2048?sort=id&direction=desc');
        $controller = new PostsController($request, new Response());
        $View = new View($controller);
        $Paginator = new MockPaginatorHelper($View);

        $paging = ['current' => 1, 'pages' => 1,  'records' => 10, 'sort' => 'id', 'direction' => 'desc', 'prevPage' => false, 'nextPage' => false];
        $Paginator->view()->set('paging', $paging);

        $expected = '<a href="/articles/edit/2048?sort=id&direction=asc" class="desc">Id</a>';
        $result = $Paginator->sort('id');
        $this->assertEquals($expected, $result);

        $expected = '<a href="/articles/edit/2048?sort=name&direction=asc">Name</a>';
        $result = $Paginator->sort('name');
        $this->assertEquals($expected, $result);
    }

    public function testPrev()
    {
        $Paginator = $this->PaginatorHelper;
        $paging = ['current' => 5, 'pages' => 10,  'records' => 100, 'sort' => 'created', 'direction' => 'asc', 'prevPage' => true, 'nextPage' => true];

        $Paginator->view()->set('paging', $paging);
        $expected = '<li class="page-item"><a class="page-link" href="/articles/edit/2048?page=4">Previous</a></li>';
        $this->assertEquals($expected, $Paginator->prev());

        $paging = ['current' => 1, 'pages' => 1,  'records' => 10, 'sort' => 'name', 'direction' => 'asc', 'prevPage' => false, 'nextPage' => false];

        $Paginator->view()->set('paging', $paging);
        $expected = '<li class="page-item"><a class="page-link" href="#" onclick="return false;">Previous</a></li>';
        $this->assertEquals($expected, $Paginator->prev());
    }

    public function testNext()
    {
        $Paginator = $this->PaginatorHelper;
        $paging = ['current' => 5, 'pages' => 10,  'records' => 100, 'sort' => 'created', 'direction' => 'asc', 'prevPage' => true, 'nextPage' => true];

        $Paginator->view()->set('paging', $paging);
        $expected = '<li class="page-item"><a class="page-link" href="/articles/edit/2048?page=6">Next</a></li>';
        $this->assertEquals($expected, $Paginator->next());

        $paging = ['current' => 1, 'pages' => 1,  'records' => 10, 'sort' => 'name', 'direction' => 'asc', 'prevPage' => false, 'nextPage' => false];

        $Paginator->view()->set('paging', $paging);
        $expected = '<li class="page-item"><a class="page-link" href="#" onclick="return false;">Next</a></li>';
        $this->assertEquals($expected, $Paginator->next());
    }

    public function testControl()
    {
        $Paginator = $this->PaginatorHelper;
        $paging = ['current' => 5, 'pages' => 10,  'records' => 100, 'sort' => 'created', 'direction' => 'asc', 'prevPage' => true, 'nextPage' => true];

        $Paginator->view()->set('paging', $paging);
        $expected = '<ul class="pagination"><li class="page-item"><a class="page-link" href="/articles/edit/2048?page=4">Previous</a></li><li class="page-item"><a class="page-link" href="/articles/edit/2048?page=1">1</a></li><li class="page-item"><a class="page-link" href="/articles/edit/2048?page=2">2</a></li><li class="page-item"><a class="page-link" href="/articles/edit/2048?page=3">3</a></li><li class="page-item"><a class="page-link" href="/articles/edit/2048?page=4">4</a></li><li class="page-item active"><a class="page-link" href="/articles/edit/2048?page=5">5</a></li><li class="page-item"><a class="page-link" href="/articles/edit/2048?page=6">6</a></li><li class="page-item"><a class="page-link" href="/articles/edit/2048?page=7">7</a></li><li class="page-item"><a class="page-link" href="/articles/edit/2048?page=8">8</a></li><li class="page-item"><a class="page-link" href="/articles/edit/2048?page=6">Next</a></li></ul>';
        $this->assertEquals($expected, $Paginator->control());

        $paging = ['current' => 1, 'pages' => 1,  'records' => 10, 'sort' => 'name', 'direction' => 'asc', 'prevPage' => false, 'nextPage' => false];

        $Paginator->view()->set('paging', $paging);
        $expected = '<ul class="pagination"><li class="page-item"><a class="page-link" href="#" onclick="return false;">Previous</a></li><li class="page-item active"><a class="page-link" href="/articles/edit/2048?page=1">1</a></li><li class="page-item"><a class="page-link" href="#" onclick="return false;">Next</a></li></ul>';
        $this->assertEquals($expected, $Paginator->control());
    }
}
