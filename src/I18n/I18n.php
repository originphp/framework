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

/*
         * I18n (Numbers,Dates, Translation etc)- set this in AppController::initialize
         *
         * Autodetect locale and language from browser:
         *
         * I18n::initialize();
         *
         * To manually set:
         *
         * I18n::initialize(['locale' => 'en_GB','language'=>'en','timezone'=>'Europe/London']);
         *
         * Set for a logged in user
         *
         * if($this->Auth->isLoggedIn()){
         *   I18n::initialize(
         *      'locale' => $this->Auth->user('locale'),
         *      'language' => $this->Auth->user('language'),
         *      'timezone' => $this->Auth->user('timezone'),
         *      );
         * }
         * else{
         * I18n::initialize()
         * }
         *
         * OR just call ;
         */
use Origin\I18n\Date;
use Origin\I18n\Number;
use NumberFormatter;
use IntlTimeZone;
use Locale;
use ResourceBundle;
use Origin\Core\StaticConfigTrait;
use Origin\Exception\Exception;

class I18n
{
    use StaticConfigTrait;
    /**
     * Holds the messages
     *
     * @var array
     */
    protected static $messages = [];
    
    /**
     * Initializes and configures I18N - use this configure or reconfigure
     * - Dates set locale and timezone
     * - Numbers sets locale and currency.
     * - sets language
     * @param array $config (locale,language,timezone,currency)
     */
    public static function initialize(array $config = [])
    {
        if (!isset($config['locale'])) {
            $config['locale'] = static::detectLocale();
        }

        setlocale(LC_ALL, $config['locale']);
        Locale::setDefault($config['locale']);

        if (!isset($config['language'])) {
            $config['language'] = static::language($config['locale']);
        }
        
        $messages = static::loadMessages($config['language']);
        if ($messages) {
            static::$messages = $messages;
        }

        if (!isset($config['timezone'])) {
            $timezone = IntlTimeZone::createDefault();
            $config['timezone'] = $timezone->getId();
        }
        
        if (!isset($config['currency'])) {
            $formatter = new NumberFormatter($config['locale'], NumberFormatter::CURRENCY);
            $config['currency'] = $formatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
        }

        Date::initialize($config);
        Number::initialize($config);
        
        static::config($config);
    }

    /**
     * Attempts to detect the locale
     *
     * @return string
     */
    public static function detectLocale()
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }
        return 'en_US';
    }

    /**
     * Returns the default timezone
     *
     * @return string timezone
     */
    public static function defaultTimezone()
    {
        $timezone = IntlTimeZone::createDefault();
        return $timezone->getId();
    }
    /**
     * Returns the default locale
     *
     * @return string locale
     */
    public static function defaultLocale()
    {
        return Locale::getDefault();
    }

    /**
     * Gets the langauge from the locale provided.
     *
     * @param string $locale
     */
    public static function language(string $locale)
    {
        return Locale::getPrimaryLanguage($locale);
    }

    /**
     * Returns a numerical array of locales supported by the
     * PHP INT extension.
     */
    public static function getLocales()
    {
        return ResourceBundle::getLocales('');
    }

    /**
     * Returns a list of locale with display name in the current language
     * if language is not set. Use this for a picklist in your app for locale selection.
     *
     * @example [[en_GB] => English (United Kingdom)]
     *
     * @param string $language
     */
    public static function locales(string $language = null)
    {
        $list = [];
        if ($language === null) {
            $language = static::getConfig('language', 'en');
        }
        $locales = self::getLocales(); //locale_get_display_region($locale, 'es');
        foreach ($locales as $locale) {
            $list[$locale] = locale_get_display_name($locale, $language);
        }

        return $list;
    }

    /**
     * Returns a list of timezones in English.
     *
     * @todo IntlTimeZone::getDisplayName
     */
    public static function timezones()
    {
        $list = array();
        $timestamp = time();
        $originalTimeZone = date_default_timezone_get();
        foreach (timezone_identifiers_list() as $key => $zone) {
            date_default_timezone_set($zone);
            $list[$zone] = 'GMT '.date('P', $timestamp).' - '. str_replace('_', ' ', $zone);
        }
        date_default_timezone_set($originalTimeZone);

        return $list;
    }

    /**
     * Translates a string
     *
     * @param string $message
     * @return void
     */
    public static function translate(string $message)
    {
        if (isset(static::$messages[$message])) {
            return static::$messages[$message];
        }
        return $message;
    }
    /**
     * Loads the message file for
     *
     * @param string $locale
     * @return void
     */
    protected static function loadMessages(string $language)
    {
        $filename = SRC  . DS . 'Messages' . DS . $language . '.php';
       
        if (file_exists($filename)) {
            $messages = include $filename;

            if (is_array($messages)) {
                return $messages;
            }
            throw new Exception("{$language}.php does not return an array");
        }
        return false;
    }
}
