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

use Origin\Controller\Controller;
use Origin\Controller\Component\FlashComponent;
use Origin\Core\Session;
use Origin\Controller\Request;
use Origin\Controller\Response;

class MockFlashComponent extends FlashComponent
{
}

class ApplesController extends Controller
{
    public $autoRender = false;

    public function index()
    {
    }
}
class FlashComponentTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $request = new Request('/apples/index');
        $this->FlashComponent = new MockFlashComponent(new ApplesController($request, new Response()));
    }
    public function testAddMessages()
    {
        $flashComponent = $this->FlashComponent;
        $session = $flashComponent->request()->session();
        $flashComponent->error('error called');
        $this->assertEquals(['error called'], $session->read('Message.error'));
        
        $flashComponent->success('success called');
        $this->assertEquals(['success called'], $session->read('Message.success'));

        $flashComponent->warning('warning called');
        $this->assertEquals(['warning called'], $session->read('Message.warning'));

        $flashComponent->info('info called');
        $this->assertEquals(['info called'], $session->read('Message.info'));

        // Test multiple messages
        $flashComponent->error('error called again');
        $this->assertEquals(['error called','error called again'], $session->read('Message.error'));
    }
}
