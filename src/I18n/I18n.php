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
declare(strict_types = 1);
namespace Origin\I18n;

use Locale;
use Origin\Utility\Date;
use Origin\Utility\Number;
use Origin\I18n\Date as I18nDate;
use Origin\Core\Exception\Exception;
use Origin\I18n\Number as I18nNumber;
use Origin\Core\Exception\InvalidArgumentException;
use Origin\I18n\Exception\LocaleNotAvailableException;

class I18n
{
    const DEFAULT_LOCALE = 'en_US';

    /**
     * The default locale to be used.
     *
     * @var string
     */
    protected static $defaulLocale = null;

    /**
     * Holds the locale to be used.
     *
     * @var string
     */
    protected static $locale = null;

    /**
     * Holds the language e.g. en
     *
     * @var string
     */
    protected static $language = null;

    /**
     * A whitelist of available locales.
     *
     * @var array
     */
    protected static $availableLocales = [];

    /**
     * Holds the messages for translation.
     *
     * @var array
     */
    protected static $messages = null;

    /**
     * Holds the locale definition
     *
     * @var array
     */
    protected static $definition = null;

    /**
     * Initializes I18n
     *
     * @param array $config (locale,language,timezone)
     * @return void
     */
    public static function initialize(array $config = []): void
    {
        $config += ['locale' => static::defaultLocale(),'language' => null,'timezone' => 'UTC'];

        static::locale($config['locale']);

        if ($config['language'] === null) {
            $config['language'] = Locale::getPrimaryLanguage($config['locale']);
        }
        self::language($config['language']);

        // Configure Date Timezone
        Date::locale(['timezone' => $config['timezone']]);

        /**
         * This is the prefered method to use locale YAML files.
         * If it is available then it will be loaded
         */
        $locale = static::loadLocale($config['locale']);

        if ($locale) {
            extract($locale);
            Date::locale([
                'timezone' => $config['timezone'],'date' => $date,'time' => $time,'datetime' => $datetime,
            ]);

            if ($currency) {
                Number::addCurrency($currency, ['before' => $before,'after' => $after]);
            } else {
                // Generic locales don't have currency, so remmove to this to work with defaults
                unset($locale['currency']);
            }
            
            unset($locale['before'],$locale['after']);
            Number::locale($locale);
        }
  
        /**
         * Originally the framework was using the Intl Extension however this became a concern as it
         * was buggy, lacking features, and problematic. However, in the case of just formating
         * numbers/dates it can be great. The main issues include input, parsing (bugs), integration with datetime pickers etc.
         */
        I18nDate::locale($config['locale']);
        I18nDate::timezone($config['timezone']);
        I18nNumber::locale($config['locale']);
    }

    /**
     * Load locale configuration if it exists, if not try to fallback on language only
     *
     * @param string $locale
     * @return array|null
     */
    protected static function loadLocale(string $locale): ?array
    {
        static::$definition = null;

        $filename = null;
        $path = CONFIG . DS . 'locales' . DS;
        if (file_exists($path . $locale .'.php')) {
            $filename = $path . $locale .'.php';
        } elseif (strpos($locale, '_') !== false) {
            list($language, $void) = explode('_', $locale, 2);
            if (file_exists($path . $language .'.php')) {
                $filename = $path . $language .'.php';
            }
        }
        if ($filename) {
            static::$definition = include $filename;
            if (! is_array(static::$definition)) {
                throw new InvalidArgumentException('Invalid Definition File');
            }
        }

        return static::$definition;
    }
    
    /**
     * Sets and gets the locale.
     *
     * @param string $locale
     *
     * @return string|void
     */
    public static function locale(string $locale = null)
    {
        if ($locale === null) {
            return static::$locale;
        }

        if (static::$availableLocales && ! in_array($locale, static::$availableLocales)) {
            throw new LocaleNotAvailableException($locale);
        }
        static::$locale = $locale;
        setlocale(LC_ALL, $locale);
        Locale::setDefault($locale); // PHP Intl Extension Friendly
        static::language(Locale::getPrimaryLanguage($locale));
    }

    /**
     * Sets or gets the default locale
     *
     * @param string $locale
     * @return string|void
     */
    public static function defaultLocale(string $locale = null)
    {
        if ($locale === null) {
            if (static::$defaulLocale === null) {
                static::$defaulLocale = self::DEFAULT_LOCALE;
            }

            return static::$defaulLocale;
        }

        if (static::$availableLocales && ! in_array($locale, static::$availableLocales)) {
            throw new LocaleNotAvailableException($locale);
        }
        static::$defaulLocale = $locale;
    }

    /**
     * Sets or gets the language.
     *
     * @param string $language
     * @return string|void
     */
    public static function language(string $language = null)
    {
        if ($language === null) {
            return static::$language;
        }
        static::$language = $language;
        static::loadMessages($language);
    }

    /**
     * Detects the locale from the accept language.
     *
     * @return string
     */
    public static function detectLocale(): string
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }

        return self::DEFAULT_LOCALE;
    }

    /**
     * Sets and gets the available locales. Only use this if you want to limit locales which
     * can be used. This forms a whitelist.
     *
     * @param array $locales ['en','es']
     *
     * @return array|void
     */
    public static function availableLocales(array $locales = null)
    {
        if ($locales === null) {
            return static::$availableLocales;
        }
        static::$availableLocales = $locales;
    }

    /**
     * Translates a string.
     * For plurals, you need to use {count}.
     *
     * @param string $message 'Hello {name} all went well', 'There are no apples|There are {count} apples'
     * @param array  $vars - to use plurals you must use the placeholder {count} ['name'=>'jon', 'count'=>5]
     * @return string
     */
    public static function translate(string $message, array $vars = []): string
    {
        /**
         * Handle if not locale set
         */
        if (static::$messages === null) {
            static::locale(static::defaultLocale());
        }

        if (isset(static::$messages[$message])) {
            $message = static::$messages[$message];
        }

        // Handle plurals
        if (strpos($message, '|') !== false && isset($vars['count'])) {
            $messages = explode('|', $message);

            if (count($messages) === 2) {
                array_unshift($messages, $messages[1]); // If zero not set use other as zero.
            }
            // use count number if set, if not use the last.
            $message = $messages[2];
            if (isset($messages[$vars['count']])) {
                $message = $messages[$vars['count']];
            }
        }

        $replace = [];
        foreach ($vars as $key => $value) {
            if (! is_array($value) && ! is_object($value)) {
                $replace['{'.$key.'}'] = $value;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Loads the message file for.
     *
     * @param string $language
     */
    protected static function loadMessages(string $language): void
    {
        $filename = APP . DS . 'Locale' . DS . $language . '.php';
        
        static::$messages = [];

        if (file_exists($filename)) {
            $messages = include $filename;
            if (! is_array($messages)) {
                throw new Exception("{$language}.php does not return an array");
            }
            static::$messages = $messages;
        }
    }
}
