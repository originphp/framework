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
        $this->assertFalse(Session::read('sessionTest'));
        Session::write('sessionTest', 'works');
        $this->assertEquals('works', Session::read('sessionTest'));
        
        Session::write('Test.status', 'ok');
        $this->assertEquals('ok', Session::read('Test.status'));
    }

    public function testCheck()
    {
        Session::write('Test.status', 'ok');
        $this->assertTrue(Session::check('Test.status'));
        $this->assertFalse(Session::check('Test.password'));
    }

    public function testDelete()
    {
        Session::write('Test.status', 'ok');
        $this->assertTrue(Session::delete('Test.status'));
        $this->assertFalse(Session::delete('Test.password'));
    }

    public function testDestroy()
    {
        Session::write('Test.status', 'ok');

        $this->assertTrue(Session::started());
        Session::destroy();
        $this->assertFalse(Session::check('Test.status'));
    }
    /**
     * @depends testDestroy
     */
    public function testCreate()
    {
        Session::destroy();
        Session::initialize();
        Session::write('Test.status', 'ok');
        $this->assertTrue(Session::check('Test.status'));
        $this->assertTrue(Session::check('Session.lastActivity'));
    }

    public function testTimeout()
    {
        Session::write('Test.status', 'ok');
        Session::write('Session.lastActivity', 0);
        Session::initialize();
        $this->assertFalse(Session::check('Test.status'));
    }
}
