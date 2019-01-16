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

namespace Origin\Test\Controller;

use Origin\Controller\Request;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    public function testParseGet()
    {
        $request = new Request('blog/home?ref=google&source=ppc');

        $this->assertEquals('google', $request->query['ref']);
        $this->assertContains('ppc', $request->query['source']);
    }
}
