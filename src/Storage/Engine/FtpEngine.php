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

use Origin\Exception\Exception;
use Origin\Exception\InvalidArgumentException;
use Origin\Storage\Exception\NotFoundException;

class FtpEngine extends BaseEngine
{
    protected $defaultConfig = [
        'host' => null,
        'username' => null,
        'password' => null,
        'port' => 21,
        'root' => null, // Must be absolute path
        'timeout' => 10,
        'ssl' => false,
        'passive' => true, // passive is the default mode used. e.g.  WinSCP
    ];

    protected $connection = null;

    public function initialize(array $config) : void
    {
        if ($this->config('host') === null) {
            throw new InvalidArgumentException('No host set');
        }

        $this->login();

        // Set ROOT
        if ($this->config('root') === null) {
            $this->config('root', ftp_pwd($this->connection));
        }

        if (! @ftp_chdir($this->connection, $this->config('root'))) {
            throw new InvalidArgumentException(sprintf('Invalid root %s.', $this->config('root')));
        }
    }

    /**
     * Logs into the ftp server
     *
     * @return void
     */
    protected function login() : void
    {
        $config = $this->config();
        extract($config);
        if ($this->config('ssl')) {
            $this->connection = @ftp_ssl_connect($host, $port, $timeout);
        } else {
            $this->connection = @ftp_connect($host, $port, $timeout);
        }

        if (! $this->connection) {
            throw new Exception(sprintf('Error connecting to %s.', $this->config('host') . ':'. $this->config('port')));
        }

        if (! @ftp_login($this->connection, $username, $password)) {
            $this->disconnect();
            throw new Exception('Invalid username or password.');
        }
        ftp_pasv($this->connection, $passive);
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

        $stream = fopen('php://temp', 'w+b'); // +b force binary
        defer($context,'fclose',$stream);
        $result = @ftp_fget($this->connection, $stream, $filename, FTP_BINARY);

        if ($result) {
            rewind($stream);
            return stream_get_contents($stream);
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

        $path = pathinfo($filename, PATHINFO_DIRNAME);
       
        if (! @ftp_chdir($this->connection, $path)) {
            $this->mkdir($path);
        }
        $stream = fopen('php://temp', 'w+b'); // +b force binary
        defer($context,'fclose',$stream);

        fwrite($stream, $data);
        rewind($stream);
        return @ftp_fput($this->connection, $filename, $stream, FTP_BINARY);
    
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
    public function delete(string $name) : bool
    {
        $filename = $this->addPathPrefix($name);

        // Prevent accidentally deleting a folder
        if (substr($name, -1) === '/') {
            return false;
        }

        if ($this->isDir($filename)) {
            return $this->rmdir($filename, true);
        }
        if (! @ftp_delete($this->connection, $filename)) {
            throw new NotFoundException(sprintf('%s does not exist', $name));
        }
    
        return true;
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

        if ($this->isDir($filename)) {
            return true;
        }
        
        return $this->fileExists($filename);
    }

    /**
     * Disconnects from server
     *
     * @return void
     */
    public function disconnect() : void
    {
        if ($this->connection) {
            ftp_close($this->connection);
        }
        $this->connection = null;
    }

    /**
     * Gets a list of items on the disk
     *
     * @return array
     */
    public function list(string $name = null) : array
    {
        $directory = $this->addPathPrefix($name);

        if (! $this->isDir($directory)) {
            throw new NotFoundException('directory does not exist');
        }

        ftp_chdir($this->connection, $this->config('root'));

        return $this->scandir($name, $this->addPathPrefix($name));
    }

    /**
     * Checks a file exists
     *
     * @param string $filename
     * @return boolean
     */
    protected function fileExists(string $filename) : bool
    {
        $path = pathinfo($filename, PATHINFO_DIRNAME);
        $list = ftp_nlist($this->connection, $path);
        if (is_array($list) and in_array($filename, $list)) {
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
    protected function mkdir(string $path) : void
    {
        ftp_chdir($this->connection, $this->config('root'));

        $parts = array_filter(explode('/', $path));
        foreach ($parts as $part) {
            if (! @ftp_chdir($this->connection, $part)) {
                ftp_mkdir($this->connection, $part);
                ftp_chmod($this->connection, 0744, $part);
                ftp_chdir($this->connection, $part);
            }
        }
        ftp_chdir($this->connection, $this->config('root'));
    }

    /**
     * Undocumented function
     *
     * @param string $directory
     * @return boolean
     */
    protected function isDir(string $directory) : bool
    {
        if (! @ftp_chdir($this->connection, $directory)) {
            return false;
        }
        ftp_chdir($this->connection, $this->config('root'));

        return true;
    }

    /**
     * Gets the contents listing of a directory
     *
     * @param string $directory
     * @param string $base
     * @return array
     */
    protected function scandir(string $directory = null, string $base) : array
    {
        $location = $this->addPathPrefix($directory);
        $files = [];

        $contents = ftp_rawlist($this->connection, $directory, true);

        if ($contents) {
            foreach ($contents as $item) {
                $result = preg_split("/[\s]+/", $item, 9);
                $file = $result[8];
                // Directory
                if (substr($result[0], 0, 1) === 'd') {
                    $subDirectory = $file;
                    if ($directory) {
                        $subDirectory = $directory . '/' . $file;
                    }

                    $recursiveFiles = $this->scandir($subDirectory, $base);
                    foreach ($recursiveFiles as $item) {
                        $files[] = $item;
                    }
                } else {
                    $files[] = [
                        'name' => ltrim(str_replace($base . DS, '', $location . DS .  $file), '/'),
                        'timestamp' => ftp_mdtm($this->connection, $location . DS . $file),
                        'size' => $result[4],
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
    protected function rmdir(string $directory, bool $recursive = true) : bool
    {
        if ($recursive) {
            $files = ftp_nlist($this->connection, $directory);
            foreach ($files as $filename) {
                if ($this->isDir($filename)) {
                    $this->rmdir($filename, true);
                    continue;
                }
                ftp_delete($this->connection, $filename);
            }
        }

        return @ftp_rmdir($this->connection, $directory);
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
        $location = str_replace('//', '/', $location); // Temp
        return $location;
    }
}
