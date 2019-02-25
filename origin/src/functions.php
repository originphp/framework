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
use Origin\Core\Debugger;
use Origin\Core\Collection;
use Origin\Core\I18n;

/**
 * Runs a backtrace.
 * @todo adjust to work in CLI
 */
function backtrace()
{
    ob_clean();
    $debugger = new Debugger();
    $debug = $debugger->backtrace();
    include VIEW.DS.'error'.DS.'debug.ctp';
    exit();
}

function pr($var)
{
    $template = '<pre>%s</pre>';
    if (php_sapi_name() === 'cli') {
        $template = "\n%s\n";
    }
    $var = print_r($var, true);
    printf($template, $var);
}

/**
 * Splits a classname into an array of namespace and class.
 *
 * @example list($namespace,$classname) = namespaceSplit(Origin\Framework\Dispatcher);
 * @param string $class Origin\Framework\Dispatcher
 * @return array ('Origin\Framework\','Dispatcher')
 */
function namespaceSplit(string $class)
{
    $namespace = null;
    $position = strrpos($class, '\\');
    if ($position !== false) {
        $namespace = substr($class, 0, $position);
        $class = substr($class, $position + 1);
    }

    return array($namespace, $class);
}

/**
 * Splits a name into an array of plugin and name.
 *
 * @example list($plugin,$name) = pluginSplit('ContactManager.contacts');
 * @param string $class 'ContactManager.contacts'
 * @return array ('ContactManager','contacts')
 */
function pluginSplit($name)
{
    $plugin = null;
    if (strpos($name, '.') !== false) {
        list($plugin, $name) = explode('.', $name, 2);
    }

    return array($plugin, $name);
}

/**
 * Translate and format a string.
 *
 * @example __('Order with id %d by user %s...', $id, $name);
 * @param string $string
 * @param mixed arg1 arg2
 * @return string formatted
 */
function __(string $string)
{
    if (!$string) {
        return '';
    }
    
    $string = I18n::translate($string);

    $arguments = array_slice(func_get_args(), 1);

    return vsprintf($string, $arguments);
}

/**
 * htmlspecialchars.
 *
 * @param mixed $text
 */
function h($text)
{
    return htmlspecialchars($text);
}

/**
 * Love this.
 *
 * @return string date('Y-m-d H:i:s')
 */
function now()
{
    return date('Y-m-d H:i:s');
}

/**
 * Returns a Origin\Core\Collection object using the array (can be any array or array of objects such as from
 * results).
 *
 * @param array $array
 * @return void
 */
function collection(array $items)
{
    return new Collection($items);
}

/**
 * Generates a UUID (Universal Unique Identifier)
 * Set version to 0100 and bits 6-7 to 10
 * @see http://tools.ietf.org/html/rfc4122#section-4.4
 * @return string
 */
function uuid()
{
    $random = random_bytes(16);
    $random[6] = chr(ord($random[6]) & 0x0f | 0x40);
    $random[8] = chr(ord($random[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($random), 4));
}

/**
 * Generates a random unpredictable unique id (Unique Identifier)
 * @param integer $length
 * @return string
 */
function uid($length=13)
{
    $random = random_bytes(ceil($length/2));
    return substr(bin2hex($random), 0, $length);
}
