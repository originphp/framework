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
use Origin\View\Helper\DateHelper;

class DateHelperTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $controller = new Controller(new Request(), new Response());
        $this->Date = new DateHelper(new View($controller));
    }
    public function testFormat()
    {
        $this->assertNull($this->Date->format(null));
        $this->assertEquals('01/03/2019', $this->Date->format('2019-03-01 16:26:00', 'd/m/Y'));
    }
}
