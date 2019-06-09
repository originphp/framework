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

namespace Origin\Engine\Storage;

use Origin\Engine\StorageEngine;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use Origin\Exception\NotFoundException;

class LocalEngine extends StorageEngine
{
    protected $defaultConfig =[
        'root' => APP . DS . 'storage'
    ];

    public function initialize(array $config)
    {
 
    }

    /**
     * Reads a file from the storage
     *
     * @param string $name
     * @return string
     */
    public function read(string $name)
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
     * @param mixed $data that can be converted to string
     * @return bool
     */
    public function write(string $name, string $data)
    {
        $filename = $this->addPathPrefix($name);

        $folder = pathinfo($filename, PATHINFO_DIRNAME);
        if (!file_exists($folder)) {
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
    public function delete(string $name)
    {
        $filename = $this->addPathPrefix($name);

        if (file_exists($filename)) {
            if (is_dir($filename)) {
                return $this->rmdir($filename);
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
    public function exists(string $name)
    {
        $filename = $this->addPathPrefix($name);
        return file_exists($filename);
    }

    /**
     * Gets a list of items on the disk
     *
     * @return array
     */
    public function list(string $name = null)
    {
        $directory = $this->addPathPrefix($name);

        if (file_exists($directory)) {
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

       
            $files = [];
            foreach ($rii as $file) {
                if ($file->isDir()) {
                    continue;
                }
                $files[]  = [
                    'name' => str_replace($directory . DS, '', $file->getPathname()),
                    'timestamp' =>  $file->getMTime(),
                    'size' => $file->getSize()
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
    protected function rmdir(string $directory)
    {
        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $filename) {
            if (is_dir($directory . DS . $filename)) {
                $this->rmdir($directory . DS . $filename);
                continue;
            }
            unlink($directory . DS . $filename);
        }

        return rmdir($directory);
    }

     /**
     * Adds the prefix
     *
     * @param string $path
     * @return string
     */
    protected function addPathPrefix(string $path = null){
        $location = $this->config('root');
        if($path){
            $location .= DS . $path;
        }
        return $location;
    }
}
