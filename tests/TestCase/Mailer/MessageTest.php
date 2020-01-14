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

namespace Origin\Test\Mailer;

use Origin\Mailer\Message;

class MessageTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $message = new Message('header: value', 'foo');
        $this->assertEquals('header: value', $message->header());
        $this->assertEquals('foo', $message->body());
        $this->assertEquals("header: value\r\n\r\nfoo", (string) $message);
    }
}
