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
declare(strict_types = 1);
namespace Origin\Process;

use RuntimeException;

abstract class BaseProcess
{
    /**
     * @var array
     */
    protected $env = [];

    /**
     * @var string
     */
    protected $directory;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var boolean
     */
    protected $outputEnabled = false;

    /**
     * @param string $directory
     * @return void
     */
    protected function setDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            throw new RuntimeException('Invalid directory');
        }
        $this->directory = $directory;
    }

    /**
     * @param array $env
     * @return void
     */
    protected function setEnv(array $env): void
    {
        foreach ($env as $key => $value) {
            $this->env[$key] = $value;
        }
    }
    /**
     * @return boolean
     */
    public function isTTY(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }

    /**
     * Escapes a command for use
     *
     * @param string|array $stringOrArray
     * @return string|array
     */
    private function escapeCommand($stringOrArray)
    {
        if (is_string($stringOrArray)) {
            return escapeshellcmd($stringOrArray);
        }

        return array_map('escapeshellarg', $stringOrArray);
    }

    /**
     * @param string|array $stringOrArray
     * @param boolean $escape
     * @return void
     */
    protected function setCommand($stringOrArray, bool $escape = true): void
    {
        if ($escape) {
            $stringOrArray = $this->escapeCommand($stringOrArray);
        }

        $this->command = is_array($stringOrArray) ? implode(' ', $stringOrArray) : $stringOrArray;
    }

    /**
     * @see https://www.php.net/manual/en/function.proc-open.php
     *
     * @param boolean $output
     * @return array
     */
    protected function descriptorspec(): array
    {
        if ($this->outputEnabled && $this->isTTY()) {
            return [
                ['file', '/dev/tty', 'r'],
                ['file', '/dev/tty', 'w'],
                ['file', '/dev/tty', 'w'],
            ];
        }

        return [
            ['pipe','r'],
            ['pipe','w'],
            ['pipe','w']
        ];
    }

    /**
     * Checks if the process ended successfully
     *
     * @return boolean
     */
    public function success(): bool
    {
        return $this->exitCode() === 0;
    }

    abstract public function exitCode(): ? int;
}
