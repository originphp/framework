<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * Installation
 *
 * $ apt-get install php-ssh2
 */
namespace Origin\Engine\Storage;

use Origin\Exception\Exception;
use Origin\Exception\NotFoundException;

class SftpEngine extends StorageEngine
{

    protected $defaultConfig =[
        'host' => null,
        'username' => null,
        'password' => null,
        'port' => null,
        'path' => null // Must be absolute path
    ];

    /**
     * SSH connection
     *
     * @var Resource
     */
    protected $connection = null;
    /**
     * Sub System
     *
     * @var string
     */
    protected $sftp = null;


    /**
     * ssh2.sftp://{$sftp}/{folder}
     *
     * @var [type]
     */
    protected $string = null;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config=[])
    {
        $this->setConfig($config);
        $this->initialize($config);
    }

    protected function connect(string $host, int $port, string $username, string $password)
    {
        try {
            $this->connection = ssh2_connect($host, $port);
        }
        catch(\Exception $ex){
            throw new Exception(sprintf('Error connecting to %s:%d', $host, $port));
        }

        try {
            ssh2_auth_password($this->connection, $username, $password);
        }
        catch(\Exception $ex){
            throw new Exception(sprtinf('Invalid username or password'));
        }
        
        try {
            $this->sftp = ssh2_sftp($this->connection);
            $sftp = intval($this->sftp);
            $this->string = "ssh2.sftp://{$sftp}" . $this->config('path');

        }
        catch(\Exception $ex){
            throw new Exception('Error requesting the SFTP subsystem');
        }
    }

    public function __destruct()
    {
        if ($this->connection) {
            /**
             * Getting segmentation faults when using ssh2_disconnect($this->connection);
             */
            $this->connection = null;
          //  unset($this->connection);
        }
    }

    public function initialize(array $config)
    {
    }

    /**
     * Reads
     *
     * @param string $name
     * @return void
     */
    public function read(string $name)
    {
        $this->initConnection();
      
        $handle = @fopen("{$this->string}/{$name}", 'r'); // $string = 'ssh2.sftp://origindev:origin@gothamdc.dev:22/home/origindev/test.txt';
        if(!$handle){
            throw new NotFoundException(sprintf('File %s does not exist',$name));
        }

        $contents = fread($handle, filesize("{$this->string}/{$name}"));      
        @fclose($handle);
        return $contents; 
    }

    public function write(string $name, string $data)
    {
        $this->initConnection();

        $folder = pathinfo($this->string . DS . $name, PATHINFO_DIRNAME);
        if(!file_exists($folder)){
            mkdir($folder,0775,true);
        }

        $handle = @fopen("{$this->string}/{$name}", 'w'); 
        if(!$handle){
            throw new Exception(sprintf('Error opening %s for writing',$name));
        }
       
        $result = fwrite($handle,$data);
            
        @fclose($handle);

        return $result;
    }

    public function delete(string $name)
    {
        $this->initConnection();
        $what = "{$this->string}/{$name}";
        if(file_exists($what)){
            if(is_dir($what)){
                return $this->rmdir($name);
            }
            return unlink($what);
        }
        throw new NotFoundException(sprintf('%s does not exist',$name));
    }

    /**
     * Recursive Delete
     *
     * @param string $directory
     * @return void
     */
    protected function rmdir(string $directory){
        $files = $this->scandir($directory);
        foreach ($files as $filename) {
            
            if(is_dir($filename)){
                pr('Is directory' . $filename);
                $this->rmdir($filename);
                continue;
            }
            unlink( "{$this->string}/{$filename}");
        }
        return rmdir("{$this->string}/{$directory}");
    }

    /**
     * Checks if file or directory exists
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name)
    {
        $this->initConnection();
        return file_exists("{$this->string}/{$name}");
    }
 
    /**
     * Lists all the files
     *
     * @param string $name
     * @return array
     */
    public function list(string $name = null)
    {
        $this->initConnection();
        if(file_exists("{$this->string}/{$name}")){
            return $this->scandir($name);
        }
        throw new NotFoundException(sprintf('%s does not exist',$name));
    }

    /**
     * Recursively Scans directory
     *
     * @param string $directory
     * @return void
     */
    protected function scandir($directory){
        $handle = @opendir($this->buildPath($directory));
        if(!$handle){
            throw new Exception(sprintf('Error opening %s',$directory));
        }
       
        $results = [];
        while (false != ($entry = readdir($handle))){
            if(in_array($entry,['.', '..'])){
                continue;
            }
            $prefix = null;
            if($directory){
                $prefix = ltrim($directory,'/') . '/';
            }
            if(is_dir($this->buildPath($directory).'/'. $entry )){
                $results  = array_merge($results, $this->scandir($directory . '/'. $entry ));
                continue;
            }

           $results[] =  $prefix . $entry;
        }
        @fclose($handle);
        return $results;
    }


    /**
     * For Scandir. Since it adds prefix /
     *
     * @param string $path
     * @return void
     */
    protected function buildPath(string $path = null){
        $p = $this->string;
        if($path){
            $p .= '/' . $path; // Need this
        }
        return $p;
    }

    protected function initConnection(){
        if(!$this->connection){
            $config = $this->config();
            $this->connect($config['host'],$config['port'],$config['username'],$config['password']);
        }
    }
}
