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

use Origin\I18n\I18n;

class MockI18n extends I18n
{
    public static function reset()
    {
        static::$config = [];
    }
}

class I18nTest extends \PHPUnit\Framework\TestCase
{

    public function testInitialize(){
        I18n::initialize(['locale'=>'en_GB']);
        $this->assertEquals('en',I18n::language());
        $this->assertEquals('en_GB',I18n::locale());
    }

    public function testDetectLocale()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-GB,en;q=0.9,es;q=0.8';
        $this->assertEquals('en_GB', MockI18n::detectLocale());

        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->assertEquals('en_US', MockI18n::detectLocale());
    }





    protected function tearDown(): void
    {
   
    }
}
