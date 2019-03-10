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

use Origin\Core\I18n;

class MockI18n extends I18n
{
    public static function reset()
    {
        static::$config = [];
    }
}

class I18nTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialize()
    {
        MockI18n::initialize(['locale' => 'en_GB','language'=>'en','timezone'=>'Europe/London']);
        $config = MockI18n::config();
        $expected = ['locale' => 'en_GB', 'language' => 'en','timezone' => 'Europe/London','currency' => 'GBP'];
        $this->assertEquals($expected, $config);
        MockI18n::reset();

        MockI18n::initialize();
        $config = MockI18n::config();
        $expected = ['locale' => 'en_US', 'language' => 'en','timezone' => 'Etc/UTC','currency' => 'USD'];
        $this->assertEquals($expected, $config);
        MockI18n::reset();
    }

    public function testDetectLocale()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-GB,en;q=0.9,es;q=0.8';
        $this->assertEquals('en_GB', MockI18n::detectLocale());

        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->assertEquals('en_US', MockI18n::detectLocale());
    }

    public function testDefaultTimezone()
    {
        $this->assertEquals('Etc/UTC', MockI18n::defaultTimezone());
    }

    public function testDefaultLocale()
    {
        $this->assertEquals('en_US', MockI18n::defaultLocale());
    }

    public function testLanguage()
    {
        $this->assertEquals('en', MockI18n::language('en_GB'));
    }

    public function testGetLocales()
    {
        $locales = MockI18n::getLocales();
        $this->assertEquals('en_GB', $locales[159]); // check one
    }

    public function testLocales()
    {
        MockI18n::config(['language'=>'en']);
        $locales = MockI18n::locales();
        $this->assertEquals('English (United Kingdom)', $locales['en_GB']); // check one
    }

    public function testTimezones()
    {
        $timezones = MockI18n::timezones();
        $this->assertEquals('GMT +00:00 - Europe/London', $timezones['Europe/London']);
    }

    public function testTranslate()
    {
        MockI18n::initialize(['language'=>'es']);
        $this->assertEquals('Esto es una prueba.', MockI18n::translate('This is a test.'));
    }

    public function tearDown()
    {
        MockI18n::initialize();
    }
}
