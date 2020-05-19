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

use Origin\Log\Log;
use Origin\I18n\I18n;
use Origin\Core\Config;
use Origin\Core\Debugger;

/**
 * Runs a backtrace.
 * @codeCoverageIgnore
 */
function backtrace(): void
{
    if (debugEnabled()) {
        $debugger = new Debugger();
        $debug = $debugger->backtrace();

        if (isConsole()) {
            $errorHandler = new Origin\Console\ErrorHandler();
            $errorHandler->render($debug, true);
        } else {
            ob_clean();
            include APP .  DS . 'Http' . DS . 'View' . DS . 'error' . DS . 'debug.ctp';
        }
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
function debug($data, bool $isHtml = false): void
{
    if (debugEnabled()) {
        $backtrace = debug_backtrace();
        $filename = str_replace(ROOT . DS, '', $backtrace[0]['file']);
        $line = $backtrace[0]['line'];
        $data = print_r($data, true);
        if ($isHtml) {
            $data = h($data);
        }

        if (isConsole()) {
            $where = "{$filename} Line: {$line}";
            $template = sprintf("# # # # # DEBUG # # # # #\n%s\n\n%s\n\n# # # # # # # # # # # # #\n", $where, $data);
        } else {
            $where = "<p><strong>{$filename}</strong> Line: <strong>{$line}</strong></p>";
            $template = sprintf('<div class="origin-debug"><p>%s</p><pre>%s</pre></div>', $where, $data);
        }
        printf("\n%s\n", $template); // allow to work with %s
    }
}

/**
 * Checks if running in console mode
 *
 * @return boolean
 */
function isConsole(): bool
{
    return (PHP_SAPI === 'cli' or PHP_SAPI === 'phpdbg');
}

/**
 * Check wether debug is enabled in a backward compatabile way.
 * @deprecated This will be deprecated in 3.0 don't rely on this.
 *
 * @return boolean
 */
function debugEnabled(): bool
{
    if (Config::exists('App.debug')) {
        return Config::read('App.debug') == true;
    }

    return Config::read('debug') == true;
}

/**
 * A print_r wrapper to print a variable in human friendly format when in debug mode.
 *
 * @param mixed $data
 * @return void
 */
function pr($data): void
{
    if (debugEnabled()) {
        $template = isConsole() ? "\n%s\n" : '<pre>%s</pre>';
        printf($template, print_r($data, true));
    }
}

/**
 * Prints a variable in JSON pretty print when in debug mode
 *
 * @param mixed $data
 * @return void
 */
function pj($data): void
{
    if (debugEnabled()) {
        $template = isConsole() ? "\n%s\n" : '<pre>%s</pre>';
        printf($template, json_encode($data, JSON_PRETTY_PRINT));
    }
}

/**
 * Splits a classname into an array of namespace and class.
 *
 * @example list($namespace,$classname) = namespaceSplit(Origin\Framework\Dispatcher);
 * @param string $class Origin\Framework\Dispatcher
 * @return array ('Origin\Framework\','Dispatcher')
 */
function namespaceSplit(string $class): array
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
 * @param string $name 'ContactManager.contacts'
 * @return array ('ContactManager','contacts')
 */
function pluginSplit($name): array
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
 * @return array
 */
function commandSplit(string $command): array
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
 * @example __('Order with id {id} by user {name}...',['id'=>$user->id,'name'=>$user->name]);
 * @param string $string
 * @param array $vars array of vars e.g ['id'=>$user->id,'name'=>$user->name]
 * @return string|null formatted
 */
function __(string $string = null, array $vars = []): ?string
{
    if ($string) {
        return I18n::translate($string, $vars);
    }

    return null;
}

/**
 * Convenient function for htmlspecialchars.
 *
 * @param string $text
 * @param string $encoding
 * @return string|null
 */
function h(string $text = null, $encoding = 'UTF-8'): ?string
{
    return htmlspecialchars($text, ENT_QUOTES, $encoding);
}

/**
 * Gets an environment vars
 *
 * @param string $variable
 * @return mixed
 */
function env(string $variable, $default = null)
{
    $out = $default;

    if (isset($_SERVER[$variable])) {
        $out = $_SERVER[$variable];
    } elseif (isset($_ENV[$variable])) {
        $out = $_ENV[$variable];
    }

    return $out;
}

/**
 * A helper function that Logs a deprecation warning and triggers an error if in debug mode
 *
 * @param string $message
 * @return void
 */
function deprecationWarning(string $message): void
{
    $trace = debug_backtrace();
    if (isset($trace[1])) {
        $message = sprintf('%s - %s. Line: %s', $message, str_replace(ROOT . DS, '', $trace[1]['file']), $trace[1]['line']);
    }

    Log::warning($message);

    if (debugEnabled()) {
        trigger_error($message, E_USER_DEPRECATED);
    }
}

/**
 * Generates a random string which can be used as a unique identifier.
 *
 * @param integer $length
 * @return string
 */
function uid(int $length = 12): string
{
    $randomBytes = random_bytes(ceil($length / 2));

    return substr(bin2hex($randomBytes), 0, $length);
}

/**
 * Shortcut for date('Y-m-d H:i:s')
 *
 * @return string date('Y-m-d H:i:s')
 */
function now(): string
{
    return date('Y-m-d H:i:s');
}
