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

namespace Origin\I18n;

use Origin\I18n\Exception\LocaleNotAvailableException;
use Origin\Exception\Exception;
use Locale;

/**
 * Removed Intl support due to number of issues ranging from bugs (parsing dates properly) or
 * impcomplete features (E.g. date picker support).
 */

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

    public static function initialize(array $config = [])
    {
        $config += ['locale' => static::$defaulLocale,'language'=>null,'timezone'=>null];
        static::locale($config['locale']);
        if($config['language']){
            static::language($config['language']); // after locale which sets the language
        }
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

        if (static::$availableLocales and !in_array($locale, static::$availableLocales)) {
            throw new LocaleNotAvailableException($locale);
        }

        setlocale(LC_ALL, $locale);
        Locale::setDefault($locale); // PHP Intl Extension Friendly

        $localeFile = ORIGIN 

        static::language(Locale::getPrimaryLanguage($locale));
    }

    /**
     * Sets or gets the default locale
     *
     * @param string $locale
     * @return void
     */
    public static function defaultLocale(string $locale = null)
    {
        if ($locale === null) {
            if(static::$defaulLocale === null){
                static::$defaulLocale = self::DEFAULT_LOCALE;
            }
            return static::$defaulLocale;
        }

        if (static::$availableLocales and !in_array($locale, static::$availableLocales)) {
            throw new LocaleNotAvailableException($locale);
        }
        static::$defaulLocale = $locale;
    }

    /**
     * Sets or gets the language.
     *
     * @param string $language
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
    public static function detectLocale()
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
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
     * For plurals, you need to use %count%.
     *
     * @param string $message 'Hello %name% all went well', 'There are no apples|There are %count% apples'
     * @param array  $vars    - to use plurals you must set count ['name'=>'jon', 'count'=>5]
     *
     * @return string
     */
    public static function translate(string $message, array $vars = [])
    {
        if(static::$messages === null){
            static::locale(static::defaultLocale());
        }
        if (isset(static::$messages[$message])) {
            $message = static::$messages[$message];
        }

        // Handle plurals
        if (strpos($message, '|') !== false and isset($vars['count'])) {
            $messages = explode('|', $message);

            if (count($messages) === 2) {
                array_unshift($messages, $messages[1]); // If zero not set use other.
            }

            if ($vars['count'] === 0) {
                $message = $messages[0];
            } elseif ($vars['count'] === 1) {
                $message = $messages[1];
            } elseif ($vars['count'] > 1) {
                $message = $messages[2];
            }
        }

        $replace = [];
        foreach ($vars as $key => $value) {
            if (!is_array($value) and !is_object($value)) {
                $replace['%'.$key.'%'] = $value;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Loads the message file for.
     *
     * @param string $locale
     */
    protected static function loadMessages(string $language)
    {
        $filename = SRC.DS.'Locale'.DS.$language.'.php';
        
        static::$messages = [];

        if (file_exists($filename)) {
            $messages = include $filename;

            if (!is_array($messages)) {
                throw new Exception("{$language}.php does not return an array");
            }

            static::$messages = $messages;
        }

        return false;
    }
}
