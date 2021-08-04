<?php

/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Origin\Core;

use \Throwable;

/**
 * Parses exception and backtrace objects into an array
 */
class Debugger
{
    /**
     * Code duplicated here so that its isolated from any errors in or before
     * functions.php.
     *
     * @param string $class
     * @return array
     */
    protected function namespaceSplit(string $class): array
    {
        $position = strrpos($class, '\\');
        if ($position === false) {
            return [null, $class];
        }

        return [substr($class, 0, $position), substr($class, $position + 1)];
    }

    /**
     * Creates the exception array
     *
     * @param \Throwable $exception
     * @return array
     */
    public function exception(Throwable $exception): array
    {
        $result = [];

        list($namespace, $class) = $this->namespaceSplit(get_class($exception));

        $result['namespace'] = $namespace;
        $result['class'] = $class;
        $result['code'] = $exception->getCode();
        $result['message'] = $exception->getMessage();

        $result['stackFrames'] = [];
        $result['stackFrames'][] = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'class' => $class,
            'function' => null,
        ];
        $stacktrace = $exception->getTrace();
        foreach ($stacktrace as $stack) {
            $result['stackFrames'][] = [
                'file' => isset($stack['file']) ? $stack['file'] : '',
                'line' => isset($stack['line']) ? $stack['line'] : '',
                'class' => isset($stack['class']) ? $stack['class'] : '',
                'function' => $stack['function'],
            ];
        }

        return $result;
    }

    /**
     * Creates the backtrace array
     *
     * @return array
     */
    public function backtrace(): array
    {
        $result = [
            'namespace' => '',
            'class' => '',
            'code' => '',
            'message' => 'Backtrace',
            'stackFrames' => [],
        ];
        $debug = debug_backtrace();
        unset($debug[0]);
        foreach ($debug as $stack) {
            $result['stackFrames'][] = [
                'file' => $stack['file'] ?? '',
                'line' => $stack['line'] ?? '',
                'class' => $stack['class'] ?? '',
                'function' => $stack['function'],
                'args' => $stack['args'],
            ];
        }

        return $result;
    }

    public static function printVar($data, string $filename, string $lineNo, bool $isHtml) : void
    {
        $data = print_r($data, true);
        if ($isHtml) {
            $data = h($data);
        }

        if (isConsole()) {
            $where = "{$filename} Line: {$lineNo}";
            $template = sprintf("# # # # # DEBUG # # # # #\n%s\n\n%s\n\n# # # # # # # # # # # # #\n", $where, $data);
        } else {
            $where = "<p><strong>{$filename}</strong> Line: <strong>{$lineNo}</strong></p>";
            $template = sprintf('<div class="origin-debug"><p>%s</p><pre>%s</pre></div>', $where, $data);
        }
        printf("\n%s\n", $template); // allow to work with %s
    }
}
