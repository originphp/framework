<?php
declare(strict_types = 1);
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

/**
 * A quick and simple .env file loader and parser
 * - Single line comments
 * - Comments after env e.g. USERNAME=foo # this is a comment
 * - Values can be quoted with " or '
 * - Multilines can parsed
 */
use Origin\Exception\Exception;
use Origin\Exception\InvalidArgumentException;

class DotEnv
{
    /**
     * Loads an .env file
     *
     * @return void
     */
    public function load(string $filename = null) : void
    {
        if ($filename === null) {
            $filename = CONFIG . '.env';
        }
        
        if (is_readable($filename)) {
            $lines = file($filename);
            $env = $this->parse($lines);
            foreach ($env as $key => $value) {
                $this->env($key, $value);
            }

            return;
        }
        throw new InvalidArgumentException(sprintf('%s could not be found.', $filename)); # Security
    }

    /**
     * Wraps the env setting
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function env(string $key, $value) : void
    {
        $_ENV[$key] = $value;
    }

    /**
     * Processes the parsed lines
     *
     * @param array $lines
     * @return array
     */
    protected function parse(array $lines) : array
    {
        $env = [];
        $capture = false;
        $quotes = '"';
        foreach ($lines as $row) {
            $row = trim($row);
            if ($row === null or $row === '' or substr($row, 0, 1) === '#') {
                continue;
            }
            
            if (substr($row, 0, 7) === 'export ') {
                $row = substr($row, 7);
            }

            # Comment Stripper
            $row = preg_replace('% # .*%', '', $row);
            
            # Parse
            if (strpos($row, '=') !== false) {
                list($key, $value) = explode('=', $row, 2);
                $key = trim(strtoupper($key));
                $env[$key] = $this->value($value);
            }

            # Capture Multiline
            if ($capture and substr($row, -1) === $quotes) {
                $env[$capture] .= "\n". rtrim($row, '"');
                $capture = false;
            } elseif ($capture) {
                $env[$capture] .= "\n". $row;
            } elseif (in_array(substr($value, 0, 1), ['"',"'"]) and ! in_array(substr($row, -1), ['"',"'"])) {
                $capture = $key;
                $quotes = substr($value, 0, 1);
            }
        }

        if ($capture) {
            throw new Exception(sprintf('Invalid value for `%s` ', $capture));
        }
        # Remove final quotes
        foreach ($env as $key => $value) {
            $env[$key] = trim($value, "\"'");
        }

        return $env;
    }

    /**
     * Prepares a value that has been parsed
     *
     * @param mixed $value
     * @return mixed
     */
    protected function value($value)
    {
        if ($value === 'null') {
            $value = null;
        } elseif ($value === 'true') {
            $value = true;
        } elseif ($value === 'false') {
            $value = false;
        } else {
            $value = str_replace('\n', "\n", $value);
        }

        return trim($value);
    }
}
