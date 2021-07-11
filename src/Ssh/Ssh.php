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
namespace Origin\Ssh;

use Exception;
use InvalidArgumentException;

/**
 * SSH component uses the php-ssh extension
 *
 * To install the extension
 * $ apt install php-ssh2
 *
 * To use public private key it needs to be in PKCS1 format, e.g. BEGIN RSA PRIVATE KEY
 * Create the user on your system and generate a key pair in PEM format.
 *
 * $ adduser jon
 * $ su jon
 * $ ssh-keygen -m PEM -b 4096 -t rsa
 * $ ssh-copy-id jon192.168.1.239
 *
 * Does not support KeyPairs encrypted with a password due to a bug which caused when libssh2 is compiled with libgcrypt (most systems), you have
 * to generate a key without a passphrase or recompile libssh2 with openssh
 *
 * @see https://bugs.php.net/bug.php?id=78661
 * @see https://bugs.php.net/bug.php?id=58573
 */

class Ssh
{
    /**
     * @var resource
     */
    private $connection;
    /**
      * @var resource
      */
    private $sftp;

    /**
     * stdout from execute
     *
     * @var string|null
     */
    private $stdout = null;

    /**
     * stderr from execute
     *
     * @var string|null
     */
    private $stderr = null;

    /**
     * Constructor
     *
     * @param array $config The following options are supported
     *  - host: hostname
     *  - username: username
     *  - password: ssh user password or leave empty for none
     *  - privateKey: location to private key file if using
     *  - publicKey: location to public key, if blank and using privateKey it will add .pub to the end of it
     *  - port: default:22
     */
    public function __construct(array $config)
    {
        $config += [
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => null,
            'privateKey' => null,
            'publicKey' => null,
            'port' => 22
        ];
        
        if (! extension_loaded('ssh2')) {
            throw new Exception('SSH2 extension not loaded');
        }

        $this->connection = ssh2_connect($config['host'], $config['port'], [
            'hostkey' => 'ssh-rsa'
        ]);

        if (! $this->connection) {
            throw new Exception('Error establishing connection to ' . $config['host']);
        }

        if ($config['privateKey']) {
            $this->authenticateWithPrivateKey($config);
        } else {
            $this->authenticate($config);
        }

        $this->sftp = ssh2_sftp($this->connection);
    }

    /**
     * Gets a list of remote files
     *
     * @param string $directory
     * @param array $options
     * @return \Origin\Ssh\RemoteFile[]
     */
    public function list(string $directory, array $options = []): array
    {
        $this->checkConnection();

        if (! is_dir($this->getRemotePath($directory))) {
            throw new InvalidArgumentException('Directory does not exist');
        }

        return $this->listRemoteFiles($directory, $options);
    }

    /**
     * @param string $directory
     * @param array $options
     * @return \Origin\Ssh\RemoteFile[]
     */
    private function listRemoteFiles(string $directory, array $options = []): array
    {
        $options += ['recursive' => false];

        $path = $this->getRemotePath($directory);

        $handle = opendir($path);
   
        $out = [];
        while (($file = readdir($handle)) !== false) {
            if (in_array($file, ['.','..'])) {
                continue;
            }
            
            if (is_dir("{$path}/{$file}")) {
                if ($options['recursive'] === true) {
                    $out = array_merge($out, $this->list($directory ."/{$file}"));
                }
                continue;
            }

            $out[] = $this->createRemoteFile($directory . '/' . $file);
        }

        return $out;
    }

    /**
     * @param string $filePath
     * @return \Origin\Ssh\RemoteFile
     */
    private function createRemoteFile(string $filePath): RemoteFile
    {
        $info = ssh2_sftp_stat($this->sftp, $filePath);

        $pathInfo = pathinfo($filePath);

        $remoteFile = new RemoteFile();
        $remoteFile->name = basename($filePath);
        $remoteFile->directory = $pathInfo['dirname'];
        $remoteFile->path = $filePath;
        $remoteFile->extension = $pathInfo['extension'] ?? null;
        $remoteFile->timestamp = $info['mtime'];
        $remoteFile->size = $info['size'];

        return $remoteFile;
    }

    /**
     * Checks if there is an open connection
     *
     * @return boolean
     */
    public function isConnected(): bool
    {
        return is_resource($this->connection);
    }

    /**
     * Disconnects
     *
     * @return boolean
     */
    public function disconnect(): bool
    {
        $status = false;

        if ($this->isConnected()) {
            /**
             * The correct way to disconnect is to set to null, only call ssh2_disconnect when using fopen. These
             * are all the errors I got during testing depending upon testing single function or whole class etc :
             *  - _libssh2_channel_free: Assertion `session' failed.
             *  - Bus error
             *  - Segmentation fault
             * @see https://bugs.php.net/bug.php?id=79631
             */
            $this->connection = null;
            $status = true;
        }

        return $status;
    }

    /**
     * Executes a command
     *
     * @param string $command
     * @return bool
     */
    public function execute(string $command): bool
    {
        $this->checkConnection();

        $this->stderr = $this->stdout = null;

        $stream = ssh2_exec($this->connection, $command);

        $stdout = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
     
        stream_set_blocking($stdout, true);
        stream_set_blocking($stderr, true);
     
        $this->stdout = stream_get_contents($stdout) ?: null;
        $this->stderr = stream_get_contents($stderr) ?: null;

        return empty($this->stderr);
    }

    /**
     * Gets the error output from execute
     *
     * @return string|null
     */
    public function getErrorOutput(): ? string
    {
        return $this->stderr;
    }

    /**
     * Gets the output from execute
     *
     * @return string|null
     */
    public function getOutput(): ? string
    {
        return $this->stdout;
    }

    /**
     * Sends a file via SCP, it will create the remote directory recursively if it does not exist.
     *
     * @param string $localFile
     * @param string $remoteFile
     * @param integer $mode
     * @return boolean
     */
    public function send(string $localFile, string $remoteFile, int $mode = 0664): bool
    {
        $this->checkConnection();

        if (! file_exists($localFile)) {
            throw new InvalidArgumentException("'{$localFile} could not be found");
        }

        # Create DIR if needed
        $directory = pathinfo($remoteFile, PATHINFO_DIRNAME);
   
        if (! is_dir($this->getRemotePath($directory)) && ! ssh2_sftp_mkdir($this->sftp, $directory, 0775, true)) {
            throw new Exception('Error creating remote directory');
        }

        return ssh2_scp_send($this->connection, $localFile, $remoteFile, $mode);
    }

    /**
     * @param string $directory
     * @return string
     */
    private function getRemotePath(string $directory): string
    {
        return 'ssh2.sftp://' . intval($this->sftp) . $directory;
    }

    /**
     * Recieves a file via SCP
     *
     * @param string $remoteFile
     * @param string $localFile
     * @return boolean
     */
    public function receive(string $remoteFile, string $localFile): bool
    {
        $this->checkConnection();

        return ssh2_scp_recv($this->connection, $remoteFile, $localFile);
    }
   
    /**
     * Checks that there is an active connection or throw an exception
     *
     * @return void
     */
    private function checkConnection(): void
    {
        if (! $this->isConnected()) {
            throw new Exception('No connection');
        }
    }

    /**
     * @param array $config
     * @return void
     */
    private function authenticate(array $config): void
    {
        if (! ssh2_auth_password($this->connection, $config['username'], $config['password'])) {
            throw new Exception('password authentication error');
        }
    }
    /**
     * If you get callback error, you are using the wrong key format. To generate a key
     * $ ssh-keygen -m PEM -t rsa -b 4096
     *
     * @param array $config
     * @return void
     */
    private function authenticateWithPrivateKey(array $config): void
    {
        if (empty($config['publicKey'])) {
            $config['publicKey'] = $config['privateKey'] . '.pub';
        }

        if (! file_exists($config['privateKey'])) {
            throw new InvalidArgumentException('Private key not found');
        }

        if (! file_exists($config['publicKey'])) {
            throw new InvalidArgumentException('Public key not found');
        }
        
        if (! ssh2_auth_pubkey_file($this->connection, $config['username'], $config['publicKey'], $config['privateKey'], (string) $config['password'])) {
            throw new Exception('Key pair authentication error');
        }
    }
}
