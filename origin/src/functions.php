<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Origin\Core\Debugger;

/**
 * Runs a backtrace.
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
    if (PHP_SAPI == 'cli') {
        $template = "\n%s\n";
    }
    $var = print_r($var, true);
    printf($template, $var);
}

/**
 * Splits a classname into an array of namespace and class.
 *
 * @example list($namespace,$classname) = namespaceSplit(Origin\Framework\Dispatcher);
 *
 * @param string $class Origin\Framework\Dispatcher
 *
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
 *
 * @param string $class 'ContactManager.contacts'
 *
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
 *
 * @param string $string
 * @param mixed arg1 arg2
 *
 * @return string formatted
 */
function __(string $string)
{
    if (!$string) {
        return '';
    }

    /**
     * @todo I18n
     *
     * @example string = I18n::translate($string);
     */
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
