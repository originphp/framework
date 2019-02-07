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

namespace Origin\Core;

use Origin\Utils\Date;
use Origin\Utils\Number;
use NumberFormatter;
use IntlTimeZone;
use Locale;
use ResourceBundle;

class I18n
{
    /**
     * Holds the current config which includes locale, currency, timezone and language for
     * locale. Changing this has no effect.
     *
     * @var array
     */
    protected static $config = [];

    /**
     * Gets the config for I18n from intialize
     *
     * @return void
     */
    public static function config()
    {
        return static::$config;
    }

    /**
     * Initializes I18N
     * Dates set locale and timezone
     * Numbers sets locale and currency.
     *
     * @param array $config
     */
    public static function initialize(array $config = [])
    {
        if (!isset($config['locale'])) {
            $config['locale'] = static::detectLocale();
        }

        setlocale(LC_ALL, $config['locale']);
        Locale::setDefault($config['locale']);

        $config['language'] = static::language($config['locale']);

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
       
        self::$config = $config;
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
            $language = 'en';
            if (isset(self::$config['language'])) {
                $language = self::$config['language'];
            }
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
}
