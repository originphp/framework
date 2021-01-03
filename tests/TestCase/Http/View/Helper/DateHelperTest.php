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

namespace Origin\Test\Http\View\Helper;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\View\View;
use Origin\Http\Controller\Controller;
use Origin\Http\View\Helper\DateHelper;

class DateHelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $controller = new Controller(new Request(), new Response());
        $this->Date = new DateHelper(new View($controller));
    }
    public function testFormat()
    {
        $this->assertNull($this->Date->format(null));
        $this->assertEquals('01/03/2019', $this->Date->format('2019-03-01 16:26:00', 'd/m/Y'));
    }

    public function testTimeAgoInWords()
    {
        // Future
        $now = date('Y-m-d H:i:s', strtotime('+1 minute'));
        $this->assertEquals('1 minute', $this->Date->timeAgoInWords($now));
        // Past
        $now = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $this->assertEquals('1 hour ago', $this->Date->timeAgoInWords($now));

        // Plural
        $now = date('Y-m-d H:i:s', strtotime('-2 day'));
        $this->assertEquals('2 days ago', $this->Date->timeAgoInWords($now));

        // Just now
        $this->assertEquals('just now', $this->Date->timeAgoInWords(now()));

        // Remaining
        $now = date('Y-m-d H:i:s', strtotime('-2 month'));
        $this->assertEquals('2 months ago', $this->Date->timeAgoInWords($now));

        $now = date('Y-m-d H:i:s', strtotime('-1 year'));
        $this->assertEquals('1 year ago', $this->Date->timeAgoInWords($now));

        $this->assertNull($this->Date->timeAgoInWords('some invalid date'));
    }
}
