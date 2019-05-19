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
use Origin\Core\Configure;
use Origin\Utility\Collection;
use Origin\I18n\I18n;
use Origin\Core\Logger;

/**
 * Runs a backtrace.
 */
function backtrace()
{
    $debugger = new Debugger();
    $debug = $debugger->backtrace();

    if (PHP_SAPI === 'cli') {
        $errorHandler = new Origin\Console\ErrorHandler();
        $errorHandler->render($debug, true);
    } else {
        ob_clean();
        include SRC . DS . 'View' . DS . 'error' . DS . 'debug.ctp';
    }
   
    exit();
}

/**
 * Debug vars to screen, it will show the line number and file where it is called from.
 * Only works in debug mode
 *
 * @param mixed $data
 * @param boolean $isHtml if set to true data will passed through htmlspecialchars
 * @return void
 */
function debug($data, bool $isHtml = false)
{
    if (Configure::read('debug')) {
        $backtrace = debug_backtrace();
        $filename = str_replace(ROOT . DS, '', $backtrace[0]['file']);
        $line = $backtrace[0]['line'];
        $data = print_r($data, true);
        if ($isHtml) {
            $data = h($data);
        }
        
        if (PHP_SAPI === 'cli') {
            $where = "{$filename} Line: {$line}";
            $template =  sprintf("\n# # # # # DEBUG # # # # #\n%s\n\n%s\n\n# # # # # # # # # # # # #\n\n", $where, $data);
        } else {
            $where = "<p><strong>{$filename}</strong> Line: <strong>{$line}</strong></p>";
            $template = sprintf('<div class="origin-debug"><p>%s</p><pre>%s</pre></div>', $where, $data);
        }
        printf($template);
    }
}

/**
 * An easy to use print_r which only works in debug mode.
 * @param mixed $data
 * @return void
 */
function pr($data)
{
    if (Configure::read('debug')) {
        $template = '<pre>%s</pre>';
        if (PHP_SAPI === 'cli') {
            $template = "\n%s\n";
        }
        $data = print_r($data, true);
        printf($template, $data);
    }
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

    return [$namespace, $class];
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

    return [$plugin, $name];
}

/**
 * Splits a command
 *
 * @param string $command app:create-user, 
 * @return void
 */
function commandSplit(string $command)
{
    $namespace = null;
    if (strpos($command, ':') !== false) {
        list($namespace, $command) = explode(':', $command, 2);
    }

    return [$namespace, $command];
}

/**
 * Translate and format a string.
 *
 * @example __('Order with id %d by user %s...', $id, $name);
 * @param string $string
 * @param mixed arg1 arg2
 * @return string formatted
 */
function __(string $string = null)
{
    if ($string) {
        $string = I18n::translate($string);

        $arguments = array_slice(func_get_args(), 1);
    
        return vsprintf($string, $arguments);
    }
    return null;
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
 * Returns a Origin\Utility\Collection object using the array (can be any array or array of objects such as from
 * results).
 *
 * @param array $array
 * @return \Origin\Utility\Collection
 */
function collection($items)
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

/**
 * A helper function that Logs a deprecation warning and triggers an error if in debug mode
 *
 * @param string $message
 * @return void
 */
function deprecationWarning(string $message)
{
    $trace = debug_backtrace();
    if (isset($trace[0])) {
        $message = sprintf('%s - %s %s', $message, str_replace(ROOT .DS, '', $trace[0]['file']), $trace[0]['line']);
    }

    $logger = new Logger('depreciation');
    $logger->warning($message);

    if (Configure::read('debug')) {
        trigger_error($message, E_USER_DEPRECATED);
    }
}
