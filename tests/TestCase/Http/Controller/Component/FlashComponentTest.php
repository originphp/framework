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
        $this->assertEquals(['error called'], $flashComponent->Session->read('Flash.error'));
        
        $flashComponent->success('success called');
        $this->assertEquals(['success called'], $flashComponent->Session->read('Flash.success'));

        $flashComponent->warning('warning called');
        $this->assertEquals(['warning called'], $flashComponent->Session->read('Flash.warning'));

        $flashComponent->info('info called');
        $this->assertEquals(['info called'], $flashComponent->Session->read('Flash.info'));

        // Test multiple messages
        $flashComponent->error('error called again');
        $this->assertEquals(['error called','error called again'], $flashComponent->Session->read('Flash.error'));
    }
}
