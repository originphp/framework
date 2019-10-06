<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Storage\Engine;

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use Origin\Exception\InvalidArgumentException;
use Origin\Storage\Exception\NotFoundException;

class LocalEngine extends BaseEngine
{
    protected $defaultConfig = [
        'root' => APP . DS . 'storage',
    ];

    public function initialize(array $config) : void
    {
        if (! file_exists($this->config('root')) and ! is_dir($this->config('root'))) {
            throw new InvalidArgumentException(sprintf('Invalid root %s.', $this->config('root')));
        }
    }

    /**
     * Reads a file from the storage
     *
     * @param string $name
     * @return string
     */
    public function read(string $name) : string
    {
        $filename = $this->addPathPrefix($name);

        if (is_file($filename)) {
            return file_get_contents($filename);
        }
        throw new NotFoundException(sprintf('File %s does not exist', $name));
    }

    /**
     * Writes to the disk
     *
     * @param string $name
     * @param string $data
     * @return bool
     */
    public function write(string $name, string $data) : bool
    {
        $filename = $this->addPathPrefix($name);

        $folder = pathinfo($filename, PATHINFO_DIRNAME);
        if (! file_exists($folder)) {
            mkdir($folder, 0744, true);
        }

        return (bool) file_put_contents($filename, $data, LOCK_EX);
    }

    /**
    * Deletes a file OR directory
    *
    * @param string $name
    * @return boolean
    */
    public function delete(string $name) : bool
    {
        $filename = $this->addPathPrefix($name);

        // Prevent accidentally deleting a folder
        if (substr($name, -1) === '/') {
            return false;
        }

        if (file_exists($filename)) {
            if (is_dir($filename)) {
                return $this->rmdir($filename, true);
            }

            return unlink($filename);
        }
        throw new NotFoundException(sprintf('%s does not exist', $name));
    }

    /**
     * Checks if file exists
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name) : bool
    {
        $filename = $this->addPathPrefix($name);

        return file_exists($filename);
    }

    /**
     * Gets a list of items on the disk
     *
     * @return array
     */
    public function list(string $name = null) : array
    {
        $directory = $this->addPathPrefix($name);

        if (file_exists($directory)) {
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            $files = [];
            foreach ($rii as $file) {
                if ($file->isDir()) {
                    continue;
                }
                $files[] = [
                    'name' => str_replace($directory . DS, '', $file->getPathname()),
                    'timestamp' => $file->getMTime(),
                    'size' => $file->getSize(),
                ];
            }

            return $files;
        }
        throw new NotFoundException('directory does not exist');
    }

    /**
     * Recursively delete a directory
     *
     * @param string $directory
     * @return bool
     */
    protected function rmdir(string $directory, bool $recursive = true) : bool
    {
        if ($recursive) {
            $files = array_diff(scandir($directory), ['.', '..']);
            foreach ($files as $filename) {
                if (is_dir($directory . DS . $filename)) {
                    $this->rmdir($directory . DS . $filename, true);
                    continue;
                }
                unlink($directory . DS . $filename);
            }
        }

        return @rmdir($directory);
    }

    /**
    * Adds the prefix
    *
    * @param string $path
    * @return string
    */
    protected function addPathPrefix(string $path = null) : string
    {
        $location = $this->config('root');
        if ($path) {
            $location .= DS . $path;
        }

        return $location;
    }
}
