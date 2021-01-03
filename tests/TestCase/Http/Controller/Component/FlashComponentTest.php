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

namespace Origin\Test\Http\Controller\Component;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Controller\Controller;
use Origin\Http\Controller\Component\FlashComponent;

class MockFlashComponent extends FlashComponent
{
}

class ApplesController extends Controller
{
    protected $autoRender = false;

    public function index()
    {
    }
}
class FlashComponentTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $request = new Request('/apples/index');
        $this->FlashComponent = new MockFlashComponent(new ApplesController($request, new Response()));
    }
    public function testAddMessages()
    {
        $flashComponent = $this->FlashComponent;
   
        $flashComponent->error('error called');
     
        $this->assertEquals([['template' => 'error','message' => 'error called']], $flashComponent->Session->read('Flash'));
        $flashComponent->Session->clear();

        $flashComponent->success('success called');
        $this->assertEquals([['template' => 'success','message' => 'success called']], $flashComponent->Session->read('Flash'));
        $flashComponent->Session->clear();

        $flashComponent->warning('warning called');
        $this->assertEquals([['template' => 'warning','message' => 'warning called']], $flashComponent->Session->read('Flash'));
        $flashComponent->Session->clear();

        $flashComponent->info('info called');
        $this->assertEquals([['template' => 'info','message' => 'info called']], $flashComponent->Session->read('Flash'));
        $flashComponent->Session->clear();

        // Test multiple messages
        $flashComponent->error('error called #1');
        $flashComponent->error('error called #2');
        $expected = [
            ['template' => 'error','message' => 'error called #1'],
            ['template' => 'error','message' => 'error called #2']
        ];
        $this->assertEquals($expected, $flashComponent->Session->read('Flash'));
    }
}
