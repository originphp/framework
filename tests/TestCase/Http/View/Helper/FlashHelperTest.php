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
use Origin\Http\View\Helper\FlashHelper;

class FlashHelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $controller = new Controller(new Request(), new Response());
        $this->Flash = new FlashHelper(new View($controller));
    }
    public function testMessages()
    {
        $this->assertNull($this->Flash->messages());
        $expected = '<div class="alert alert-danger" role="alert">holy moly</div>';
        $this->Flash->Session->write('Flash', [
            ['template' => 'error','message' => 'holy moly']
        ]);
        $this->assertEquals($expected, $this->Flash->messages());
    }
}
