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

use Locale;

/**
 * Removed Intl support due to number of issues ranging from bugs (getting info, parsing dates properly) or 
 * impcomplete features (E.g. date picker support)
 */

class I18n
{
   
    /**
     * The default locale to be used
     *
     * @var string
     */
    const DEFAULT_LOCALE = 'en_US';

    /**
     * Holds the locale to be used
     *
     * @var string
     */
    protected static $locale = null;

    /**
     * A whitelist of available locales
     *
     * @var array
     */
    protected static $availableLocales = [];


    protected static $language = 'en';

    /**
     * Holds the messages for translation
     *
     * @var array
     */
    protected static $messages = [];

    /**
     * Sets and gets the locale
     *
     * @param string $locale
     * @return string|void
     */
    public static function locale(string $locale=null){

        if($locale === null){
            return static::$locale;
        }

        if(static::$locales AND !in_array($locale,static::$locales)){
            throw new LocaleNotAvailableException($locale);
        }

        set_locale(LC_ALL,$locale);
        Locale::setDefault($locale); // PHP Intl Extension Friendly
    }

    /**
     * Sets or gets the language
     *
     * @param string $language
     * @return void
     */
    public static function language(string $language = null){
        if($language === null){
            return static::$language;
        }
        static::$language = $language;
        static::loadMessages($language);
    }

    /**
     * Detects the locale from the accept language
     *
     * @return string
     */
    public static function detectLocale(){
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }
        return static::$defaultLocale;
    }

    /**
     * Sets and gets the available locales. Only use this if you want to limit locales which
     * can be used. This forms a whitelist
     *
     * @param array $locales ['en','es']
     * @return array|void
     */
    public static function availableLocales(array $locales = null){
        if($locales === null){
            return static::$availableLocales;
        }
        static::$availableLocales = $locales;
    }

    /**
     * Translates a string.
     * For plurals, you need to use %count% 
     *
     * @param string $message  'Hello %name% all went well', 'There are no apples|There are %count% apples'
     * @return string
     */
    public static function translate(string $message,array $vars=[])
    {
        if (isset(static::$messages[$message])) {
            $message = static::$messages[$message];
            // Handle plurals
            if(strpos($message,'|') !== false AND isset($vars['%count%'])){
                $messages = explode('|',$message);
              
                if($vars['%count%'] === 0){
                    $message = $messages[0]; // 0 count
                }
                elseif((count($messages) == 2 AND $vars['%count%'] > 0) OR (count($messages) == 3 AND $vars['%count%'] === 1)){
                    $message = $messages[1]; 
                }
                else{
                    $message = $messages[2]; 
                }
                
                if($vars['%count%'] > 1 AND count($message)==3){
                    $message = $message[1];
                }
            }

            $replace = [];
            foreach ($replace as $key => $value) {
                if (!is_array($value) and !is_object($value)) {
                    $replace['%'. $key . '%'] = $value;
                }
            }

            return strtr($message,$replace);
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
                static::$messages = $messages;
            }
            throw new Exception("{$language}.php does not return an array");
        }
        return false;
    }
}
