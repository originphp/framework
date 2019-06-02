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

class DiskEngine extends StorageEngine
{
    protected $path = null;

    protected $defaultConfig =[
        'path' => APP . DS . 'storage'
    ];

    public function initialize(array $config)
    {
        $this->path = $config['path'];
    }

    /**
     * Reads a file from the storage
     *
     * @param string $name
     * @return string
     */
    public function read(string $name) {
        $filename = $this->path . DS . $name;
        if(is_file($filename)){
            return file_get_contents($filename);
        }
        throw new NotFoundException(sprintf('File %s does not exist',$name));
    }

    /**
     * Writes to the disk
     *
     * @param string $name
     * @param mixed $data that can be converted to string
     * @return bool
     */
    public function write(string $name,string $data)
    {
       $folder = pathinfo($this->path . DS . $name,PATHINFO_DIRNAME);
       if(!file_exists($folder)){
           mkdir($folder,0775,true);
       }
       return file_put_contents($this->path . DS . $name, $data,LOCK_EX);
    }

    /**
     * Deletes a file OR directory
     *
     * @param string $name
     * @return boolean
     */
    public function delete(string $name)
    {
        $filename = $this->path . DS . $name;

        if(file_exists($filename)){
            if(is_dir($filename)){
                return $this->rmdir($filename);
            }
            return unlink($filename);
        }     
        throw new NotFoundException(sprintf('%s does not exist',$name));
    }

    /**
     * Checks if file exists
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name)
    {
        return file_exists($this->path . DS . $name);
    }

    /**
     * Gets a list of items on the disk
     *
     * @return array
     */
    public function list(string $path = null)
    {
        $directory = $this->path;
        if($path){
            $directory .= DS . $path;
        }
        if (file_exists($directory)) {
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            $files = [];
            foreach ($rii as $file) {
                if ($file->isDir()){ 
                    continue;
                }
                $files[] = str_replace($directory . DS ,'',$file->getPathname()); 
            }
            sort($files);
            return $files;
        }
        throw new NotFoundException(sprintf('%s does not exist',$path));
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
            if(is_dir($directory . DS . $filename)){
                $this->rmdir($directory . DS . $filename);
                continue;
            }
            unlink($directory . DS . $filename);
        }

        return rmdir($directory);
    }
}