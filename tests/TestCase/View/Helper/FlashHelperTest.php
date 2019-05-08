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
use Origin\Controller\Controller;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\View\Helper\FlashHelper;

class FlashHelperTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $controller = new Controller(new Request(), new Response());
        $this->Flash = new FlashHelper(new View($controller));
    }
    public function testMessages()
    {
        $this->assertNull($this->Flash->messages());
        $expected = '<div class="alert alert-danger" role="alert">holy moly</div>';
        $this->Flash->Session->write('Flash', ['error'=>['holy moly']]);
        $this->assertEquals($expected, $this->Flash->messages());
    }
}
