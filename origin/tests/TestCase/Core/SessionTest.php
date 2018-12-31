<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Core;

use Origin\Core\Session;

class SessionTest extends \PHPUnit\Framework\TestCase
{
    public function testWrite()
    {
        Session::write('sessionTest', 'works');
        $this->assertTrue(isset($_SESSION['sessionTest']));
        $this->assertEquals('works', $_SESSION['sessionTest']);
    }

    public function testRead()
    {
        Session::write('sessionTest', 'works');
        $this->assertEquals('works', Session::read('sessionTest'));
    }
}
