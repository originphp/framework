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

use Origin\Storage\Engine\BaseEngine;
use Origin\Exception\NotFoundException;
use Origin\Exception\Exception;

use phpseclib\Net\SFTP;
use phpseclib\Crypt\RSA;
use Origin\Exception\InvalidArgumentException;

/**
 * To install phpseclib
 * composer require phpseclib/phpseclib
 */

class SftpEngine extends BaseEngine
{
    protected $defaultConfig =[
        'host' => null,
        'username' => null,
        'password' => null,
        'port' => 22,
        'root' => null, // Must be absolute path
        'privateKey' => null, // path to public key file on server,
        'timeout' => 10
    ];

    /**
     * SFTP Object
     *
     * @var \phpseclib\Net\SFTP
     */
    protected $connection = null;

    public function initialize(array $config)
    {
        //"phpseclib/phpseclib
        if (!class_exists(SFTP::class)) {
            throw new Exception('phpseclib not installed.');
        }
        if ($this->config('host') === null) {
            throw new InvalidArgumentException('No host set');
        }

        if ($this->config('privateKey') and !file_Exists($this->config('privateKey'))) {
            throw new NotFoundException(sprintf('%s could not be found'));
        }

        $this->connection = new SFTP(
            $this->config('host'),
            $this->config('port'),
            $this->config('timeout')
        );
       
        $this->login();

        // Set ROOT
        if ($this->config('root') === null) {
            $this->config('root', $this->connection->pwd());
        }
    }

    protected function login()
    {
        $config = $this->config();
        extract($config);
        if ($this->config('privateKey')) {
            $password = new RSA();
            if ($this->config('password')) {
                $password->setPassword($this->config('password')); # Must be set before loadKey
            }
            $privateKey = $this->config('privateKey');
            if (substr($privateKey, 0, 5) !== '-----') {
                $privateKey = file_get_contents(privateKey);
            }
            $password ->loadKey($privateKey);
        }
       
        if (!$this->connection->login($username, $password)) {
            throw new Exception('Invalid username or password');
        }
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

        if ($this->connection->is_file($filename)) {
            return $this->connection->get($filename);
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
        if (!$this->connection->stat($folder)) {
            $this->connection->mkdir($folder, 0744, true);
        }

        return $this->connection->put($filename, $data);
    }

    /**
     * Deletes a file OR directory
     *
     * @internal issue with phpseclib not deleting empty directories without recursive probably due to ./..
     * @param string $name
     * @return boolean
     */
    public function delete(string $name)
    {
        $filename = $this->addPathPrefix($name);

        // Prevent accidentally deleting a folder
        if (substr($name, -1) === '/') {
            return false;
        }
        
        if ($this->connection->stat($filename)) {
            return $this->connection->delete($filename, true);
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
        return (bool) $this->connection->stat($filename);
    }

    /**
     * Gets a list of items on the disk
     *
     * @return array
     */
    public function list(string $name = null)
    {
        $directory = $this->addPathPrefix($name);

        if (!$this->connection->is_dir($directory)) {
            throw new NotFoundException('directory does not exist');
        }
        $this->base = $this->addPathPrefix($name);
        return $this->scandir($name);
    }

    protected function scandir(string $directory = null)
    {
        $location = $this->addPathPrefix($directory);
        $files = [];

        $contents = $this->connection->rawlist($location);

        if ($contents) {
            foreach ($contents as $file => $info) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }
               
                if ($info['type'] === 1) {
                    $files[] = [
                        'name' => str_replace($this->base . DS, '', $location . DS .  $file),
                        'timestamp' => $info['mtime'],
                        'size' => $info['size']
                    ];
                } elseif ($info['type'] === 2) {
                    $subDirectory = $file;
                    if ($directory) {
                        $subDirectory = $directory . '/' . $file;
                    }

                    $recursiveFiles = $this->scandir($subDirectory);
                    foreach ($recursiveFiles as $item) {
                        $files[] = $item;
                    }
                }
            }
        }

        return $files;
    }

    /**
    * Adds the prefix
    *
    * @param string $path
    * @return string
    */
    protected function addPathPrefix(string $path = null)
    {
        $location = $this->config('root');
        if ($path !== null) {
            $location .= DS . $path;
        }
        return $location;
    }
}
