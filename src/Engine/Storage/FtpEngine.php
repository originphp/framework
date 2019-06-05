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

use Origin\Exception\NotFoundException;
use Origin\Exception\Exception;

class FtpEngine extends StorageEngine
{
    protected $defaultConfig =[
        'host' => null,
        'username' => null,
        'password' => null,
        'port' => 21,
        'root' => null, // Must be absolute path
        'timeout' => 10,
        'ssl' => false,
        'passive' => false,
        'root' => null
    ];

    protected $connection = null;

    public function initialize(array $config)
    {
        if($this->config('host') === null){
            throw new InvalidArgumentException('No host set');
        }

        $this->login();

        // Set ROOT
        if($this->config('root') === null){
            $this->config('root',ftp_pwd($this->connection));
        }
    }


    protected function login(){
        $config = $this->config();
        extract($config);
        if($this->config('ssl')){
            $this->connection = @ftp_ssl_connect($host,$port, $timeout);
        }
        else{
            $this->connection = @ftp_connect($host,$port, $timeout);
        }

        if(!$this->connection){
            throw new Exception(sprintf('Error connecting to %s.',$this->config('host')));
        }

        if(!@ftp_login($this->connection,$username,$password)){
            throw new Exception('Invalid username or password');
        }
        ftp_pasv($this->connection,$passive);    
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

        $stream = fopen('php://temp', 'w+b'); // +b force binary
        $result = @ftp_fget($this->connection, $stream,$filename,FTP_BINARY);
        rewind($stream);
        $data = stream_get_contents($stream);
        fclose($stream);

        if($result){
            return $data;
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

        $path = pathinfo($filename, PATHINFO_DIRNAME);
        if (!@ftp_chdir($this->connection,$path)) {
            $this->mkdir($path);
        }
        $stream = fopen('php://temp', 'w+b'); // +b force binary
        fwrite($stream, $data);
        rewind($stream);
        $result = @ftp_fput($this->connection,$filename, $stream,FTP_BINARY);
        fclose($stream);
        return $result;
        /*
        $tmpfile = sys_get_temp_dir() . DS . uniqid();
        file_put_contents($tmpfile,$data);
        return ftp_put($this->connection,$filename,$tmpfile,FTP_BINARY);*/
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

        if($this->isDir($filename)){
            return $this->rmdir($filename);
        }
        if($this->fileExists($filename)){
            return ftp_delete($this->connection,$filename);
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

        if($this->isDir($filename)){
            return true;
        }
        
        return $this->fileExists($filename);
    }


      /**
     * Gets a list of items on the disk
     *
     * @return array
     */
    public function list(string $name = null)
    {
        $directory = $this->addPathPrefix($name);

        if(!$this->isDir($directory)){
            throw new NotFoundException('directory does not exist');
        }
        ftp_chdir($this->connection,$this->config('root'));

        $this->base = $this->addPathPrefix($name);
        return $this->scandir($name);
    }

    protected function fileExists(string $filename){
        $path = pathinfo($filename, PATHINFO_DIRNAME);
        $list = ftp_nlist($this->connection,$path);
        if(is_array($list) AND in_array($filename,$list)){
            return true;
        }
        return false;
    }


    /**
     * Creates directories recrusively
     *
     * @param string $path
     * @return void
     */
    protected function mkdir(string $path){
        @ftp_chdir($this->connection, $this->config('root')); 

        $parts = array_filter(explode('/',$path)); 
        foreach($parts as $part){
            if(!@ftp_chdir($this->connection, $part)){
                ftp_mkdir($this->connection, $part);
                ftp_chmod($this->connection, 0744, $part);
                ftp_chdir($this->connection, $part);
            }
        }
        @ftp_chdir($this->connection, $this->config('root')); 
    }

    /**
     * Undocumented function
     *
     * @param string $directory
     * @return boolean
     */
    protected function isDir(string $directory){
        if(!@ftp_chdir($this->connection, $directory)){
            return false;
        }
        ftp_chdir($this->connection,$this->config('root'));
        return true;
    }

    protected function scandir(string $directory = null){
        $location = $this->addPathPrefix($directory);
        $files = [];

        $contents = ftp_rawlist($this->connection, $directory,true);

        if($contents){
            foreach($contents as $item){
                $result = preg_split("/[\s]+/", $item, 9);
                $file = $result[8];
                // Directory
                if(substr($result[0],0,1) === 'd'){
                    $subDirectory = $file;
                    if ($directory) {
                        $subDirectory = $directory . '/' . $file;
                    }

                    $recursiveFiles = $this->scandir( $subDirectory );
                    foreach($recursiveFiles as $item){
                        $files[] = $item;
                    }
                }
                else{
                    $files[] = [
                        'name' => ltrim(str_replace($this->base . DS,'', $location . DS .  $file),'/'),
                        'timestamp' => ftp_mdtm($this->connection,$location . DS . $file),
                        'size' => $result[4]
                    ];
                }
            }
        }

        return $files;
    }

    /**
     * Recursively delete a directory.
     * @internal ftp_rmdir requires folder to be empty
     *
     * @param string $directory
     * @return bool
     */
    protected function rmdir(string $directory)
    {
        $files = ftp_nlist($this->connection,$directory);
        foreach ($files as $filename) {
            if ($this->isDir($directory . DS . $filename)) {
                $this->rmdir($directory . DS . $filename);
                continue;
            }
            ftp_delete($this->connection,$filename);
        }

        return ftp_rmdir($this->connection,$directory);
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
        $location = str_replace('//','/',$location); // Temp 
        return $location;
    }
}
