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
use Origin\Log\Log;

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
            $template =  sprintf("# # # # # DEBUG # # # # #\n%s\n\n%s\n\n# # # # # # # # # # # # #\n", $where, $data);
        } else {
            $where = "<p><strong>{$filename}</strong> Line: <strong>{$line}</strong></p>";
            $template = sprintf('<div class="origin-debug"><p>%s</p><pre>%s</pre></div>', $where, $data);
        }
        printf("\n%s\n", $template); // allow to work with %s
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
 * @return array
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
 * @example __('Order with id {id} by user {name}...',['id'=>$user->id,'name'=>$user->name]);
 * @param string $string
 * @param mixed arg1 arg2
 * @return string formatted
 */
function __(string $string = null, array $vars = [])
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
 * @return string
 */
function h(string $text = null, $encoding = 'UTF-8')
{
    return htmlspecialchars($text, ENT_QUOTES, $encoding);
}

/**
 * Gets or sets an Environment variable
 *
 * @param string $variable
 * @return string|null
 */
function env(string $variable, string $value = null)
{
    if ($value === null) {
        if (isset($_SERVER[$variable])) {
            return $_SERVER[$variable];
        }
        if (isset($_ENV[$variable])) {
            return $_ENV[$variable];
        }
        return null;
    }

    $_ENV[$variable] = $value;
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
 * The OriginPHP default password hasher
 *
 * @param string $password
 * @return string
 */
function hashPassword(string $password)
{
    deprecationWarning('Deprecated: Use Security::hashPassword instead');
    return password_hash($password, PASSWORD_DEFAULT);
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

    Log::warning($message);

    if (Configure::read('debug')) {
        trigger_error($message, E_USER_DEPRECATED);
    }
}

/**
 * Helper Functions
 */


/**
* Checks if a string contains a substring
*
* @param string $needle
* @param string $haystack
* @return bool
*/
function contains(string $needle, string $haystack) : bool
{
    if (!empty($needle)) {
        return (mb_strpos($haystack, $needle) !== false);
    }
    return false;
}


/**
 * Gets part of the string from the left part of characters
 *
 * @param string $characters   :
 * @param string $string    key:value
 * @return string|null      key
 */
function left(string $characters, string $string) : ?string
{
    if (!empty($characters)) {
        $position = mb_strpos($string, $characters);
        if ($position === false) {
            return null;
        }
        return mb_substr($string, 0, $position);
    }
    return null;
}

/**
 * Gets part of the string from the right part of characters
 *
 * @param string $characters   :
 * @param string $string    key:value
 * @return string|null     value
 */
function right(string $characters, string $string) : ?string
{
    if (!empty($characters)) {
        $position = mb_strpos($string, $characters);
        if ($position === false) {
            return null;
        }
        return mb_substr($string, $position + mb_strlen($characters));
    }
    return null;
}

/**
 * Checks if a string starts with another string
 *
 * @param string $needle
 * @param string $haystack
 * @return boolean
 */
function begins(string $needle, string $haystack) : bool
{
    $length = mb_strlen($needle);
    return (mb_substr($haystack, 0, $length) == $needle);
}

/**
 * Checks if a string ends with another string
 *
 * @param string $needle
 * @param string $haystack
 * @return boolean
 */
function ends(string $needle, string $haystack) : bool
{
    $length = mb_strlen($needle);
    return (mb_substr($haystack, -$length, $length) == $needle);
}

/**
 * Replaces text in strings.
 *
 * @internal str_replace works with multibyte string see https://php.net/manual/en/ref.mbstring.php#109937
 * @param string $needle
 * @param string $with
 * @param string $haystack
 * @param array $options (insensitive = false)
 *  - insensitive: default false. case-insensitive replace
 * @return string
 */
function replace(string $needle, string $with, string $haystack, array $options=[]) : string
{
    $options += ['insensitive'=>false];
    if ($options['insensitive']) {
        return str_ireplace($needle, $with, $haystack);
    }
    return str_replace($needle, $with, $haystack);
}

/**
 * Returns the length of a string
 *
 * @param string $string
 * @return integer
 */
function length(string $string) : int
{
    return mb_strlen($string);
}

/**
 * Converts a string to lower case
 *
 * @param string $string
 * @return string
 */
function lower(string $string) :string
{
    return mb_strtolower($string);
}

/**
 * Converts a stirng to uppercase
 *
 * @param string $string
 * @return string
 */
function upper(string $string) :string
{
    return mb_strtoupper($string);
}
