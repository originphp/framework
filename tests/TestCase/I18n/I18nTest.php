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

namespace Origin\Test\I8n;

use Origin\I18n\I18n;
use Origin\Utility\Number;
use Origin\Core\Exception\Exception;
use Origin\I18n\Exception\LocaleNotAvailableException;

class I18nTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialize()
    {
        I18n::initialize(['locale' => 'en_GB']);
        $this->assertEquals('en', I18n::language());
        $this->assertEquals('en_GB', I18n::locale());
        $this->assertEquals('£10,000', Number::currency(10000));
    }

    /**
     * Reach the unsetting of the currency
     */
    public function testLocaleNoCurrency()
    {
        $locale = [
            'name' => 'English (World)',
            'decimals' => '.',
            'thousands' => ',',
            'currency' => null,
            'before' => '¤',
            'after' => null,
            'date' => 'd/m/Y',
            'time' => 'g:i a',
            'datetime' => 'd/m/Y, g:i a',
        ];

        file_put_contents(CONFIG . DS . 'locales' . DS . 'en_001' .'.php', $this->localeToString($locale));
        I18n::initialize(['locale' => 'en_001']);
        $this->assertEquals('$10,000', Number::currency(10000));
        unlink(CONFIG . DS . 'locales' . DS . 'en_001' .'.php');
    }

    private function localeToString(array $data)
    {
        return "<?php\nreturn ".var_export($data, true).';';
    }

    public function testLocaleGeneric()
    {
        $locale = [
            'name' => 'English (World)',
            'decimals' => '.',
            'thousands' => ',',
            'currency' => null,
            'before' => '¤',
            'after' => null,
            'date' => 'd/m/Y',
            'time' => 'g:i a',
            'datetime' => 'd/m/Y, g:i a',
        ];

        file_put_contents(CONFIG . DS . 'locales' . DS . 'en' .'.php', $this->localeToString($locale));
        I18n::initialize(['locale' => 'en_NOT_EXIST']);
        $this->assertEquals('$10,000', Number::currency(10000));
        unlink(CONFIG . DS . 'locales' . DS . 'en' .'.php');
    }

    public function testDetectLocale()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-GB,en;q=0.9,es;q=0.8';
        $this->assertEquals('en_GB', I18n::detectLocale());

        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->assertEquals('en_US', I18n::detectLocale());
    }

    public function testDefaultLocale()
    {
        $this->assertEquals('en_US', I18n::defaultLocale());
        I18n::defaultLocale('en_GB');
        $this->assertEquals('en_GB', I18n::defaultLocale());
    }

    public function testAvailableLocales()
    {
        $this->assertEquals([], I18n::availableLocales());
        I18n::availableLocales(['en_US','en_GB']);
        $this->assertEquals(['en_US','en_GB'], I18n::availableLocales());
    }

    /**
     * @depends testAvailableLocales
     */
    public function testDefaultLocaleException()
    {
        $this->expectException(LocaleNotAvailableException::class);
        I18n::availableLocales(['en_US','en_GB']);
        I18n::defaultLocale('fr_FR');
    }

    /**
    * @depends testAvailableLocales
    */
    public function testLocaleException()
    {
        $this->expectException(LocaleNotAvailableException::class);
        I18n::availableLocales(['en_US','en_GB']);
        I18n::Locale('fr_FR');
    }

    public function testTranslate()
    {
        I18n::initialize(['locale' => 'es_ES']);
        $this->assertEquals('Hola Mundo', __('hello world'));
        $this->assertEquals('Hola Jim', __('Hello {name}', ['name' => 'Jim']));
        $this->assertEquals('Hay 0 manzanas', __('There is one apple|There are {count} apples', ['count' => 0]));
        $this->assertEquals('Hay una manzana', __('There is one apple|There are {count} apples', ['count' => 1]));
        $this->assertEquals('Hay 2 manzanas', __('There is one apple|There are {count} apples', ['count' => 2]));
    }

    public function testLoadMessagesException()
    {
        $this->expectException(Exception::class);
        I18n::initialize(['locale' => 'ar_AR']);
        __('It really does not matter');
    }

    protected function tearDown(): void
    {
        I18n::initialize();
        I18n::availableLocales([]);
        I18n::defaultLocale('en_US'); // restore
    }
}
