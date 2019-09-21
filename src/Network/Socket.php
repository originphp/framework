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

namespace Origin\Network;

use Origin\Network\Exception\SocketException;
use Origin\Exception\InvalidArgumentException;
use Origin\Network\Exception\SocketTimeoutException;

/**
 * The OriginPHP Socket class
 *
 * $socket = new Socket([
 *  'host' => 'localhost',
 *   'protocol' => 'tcp',
 *   'port' => 80,
 *   'timeout' => 30,
 *   'persistent' => false,
 * ]);
 *
 * if($socket->connect()){
 *   $socket->write("HELO mydomain.com\r\n");
 *   $result = $socket->read();
 * }
 *
 * $socket->disconnect();
 *
 *
 * @todo Refactor email transport to use Socket
 */
class Socket
{
    protected $config = [];

    /**
     * Holds the socket connection
     *
     * @var resource
     */
    protected $connection = null;

    protected $lastError = null;

    protected $errors = [];

    public function __construct(array $options = [])
    {
        $options += [
            'host' => 'localhost',
            'protocol' => 'tcp',
            'port' => 80,
            'timeout' => 30,
            'persistent' => false,
        ];
        $this->config = $options;
    }

    /**
     * Connects to the socket, if there its already connected,
     *
     * @return bool
     */
    public function connect() : bool
    {
        if ($this->connection) {
            $this->disconnect();
        }

        $context = empty($this->config['context']) ? null : $this->config['context'];
   
        set_error_handler([$this, 'errorHandler']);
        $this->connection = stream_socket_client(
            $this->socketAddress(),
            $errorNumber,
            $errorMessage,
            $this->config['timeout'],
            $this->config['persistent']  ? STREAM_CLIENT_PERSISTENT : STREAM_CLIENT_CONNECT, // flags
            stream_context_create($context)
        );
        restore_error_handler();

        if (! empty($errorNumber) or ! empty($errorMessage)) {
            $this->lastError("{$errorNumber}:{$errorMessage}");
            throw new SocketException($errorMessage);
        }
    
        /**
         * Throw exception on ErrorHandler errors (can be multiple)
         */
        if (! $this->connection and $this->errors) {
            throw new SocketException(implode("\n", $this->errors));
        }

        $connected = is_resource($this->connection);
        if ($connected) {
            stream_set_timeout($this->connection, $this->config['timeout']);
        }

        return $connected;
    }

    /**
     * Enables (or disables crypto)
     *
     * @see https://www.php.net/manual/en/function.stream-socket-enable-crypto.php
     * @param string $type sslv3,sslv23,tls,tlsv1,tlsv11,tlsv12
     * @param bool $client  default: true. set to false if server
     * @param boolean $enable
     * @return boolean
     */
    public function enableCrypto(string $type, bool $client = true, bool $enable = true) : bool
    {
        // sslv2 not supported . e.g SSLv2 unavailable in this PHP version
        $map = [
            'sslv3_client' => STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
            'sslv23_client' => STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
            'tls_client' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
            'tlsv1_client' => STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT,
            'tlsv11_client' => STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
            'tlsv12_client' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
            'sslv3_server' => STREAM_CRYPTO_METHOD_SSLv3_SERVER,
            'sslv23_server' => STREAM_CRYPTO_METHOD_SSLv23_SERVER,
            'tls_server' => STREAM_CRYPTO_METHOD_TLS_SERVER,
            'tlsv1_server' => STREAM_CRYPTO_METHOD_TLSv1_0_SERVER,
            'tlsv11_server' => STREAM_CRYPTO_METHOD_TLSv1_1_SERVER,
            'tlsv12_server' => STREAM_CRYPTO_METHOD_TLSv1_2_SERVER,
        ];
      
        $encryptionMethod = $type .'_'. ($client ? 'client' : 'server');
        if (! array_key_exists($encryptionMethod, $map)) {
            throw new InvalidArgumentException('Invalid Encryption scheme');
        }

        try {
            $result = stream_socket_enable_crypto(
                $this->connection,
                $enable,
                $map[$encryptionMethod]
            );
        } catch (\Exception $exception) {
            throw new SocketException($exception->getMessage());
        }

        return $result;
    }

    /**
     * Writes to the sock
     *
     * @param string $data
     * @return int
     */
    public function write(string $data) :int
    {
        if (! $this->isConnected()) {
            return 0;
        }

        $bytes = fwrite($this->connection, $data);

        return $bytes === false ? 0 : $bytes;
    }

    /**
     * Reads from a connection
     *
     * @param integer $bytes
     * @return string|null
     * @throws \Origin\Network\Exception\SocketTimeoutException
     */
    public function read(int $bytes = 1024) : ?string
    {
        if (! $this->isConnected()) {
            return null;
        }
        $buffer = null;

        if (! feof($this->connection)) {
            $buffer = fread($this->connection, $bytes);
            $info = stream_get_meta_data($this->connection);
            if ($info['timed_out']) {
                throw new SocketTimeoutException('Connection timed out');
            }
        }

        return $buffer;
    }

    /**
     * Returns the socket connection
     *
     * @return resource|null
     */
    public function connection()
    {
        return $this->connection;
    }
   
    /**
     * Checks if it is connected
     *
     * @return boolean
     */
    public function isConnected() : bool
    {
        return is_resource($this->connection);
    }

    /**
     * Disconnects the current connection
     *
     * @return boolean
     */
    public function disconnect() : bool
    {
        $connected = is_resource($this->connection);
        if ($connected) {
            $connected = ! fclose($this->connection);
        }
        if (! $connected) {
            $this->connection = null;
        }

        return ! $connected;
    }

    /**
     * Last error setter and getter
     *
     * @param string $error
     * @return string|null
     */
    public function lastError(string $error = null) : ?string
    {
        if ($error === null) {
            return $this->lastError;
        }

        return $this->lastError = $error;
    }

    /**
     * The error handler used when creating the connection
     *
     * @param int $code
     * @param string $message
     * @return void
     */
    public function errorHandler(int $code, string $message) : void
    {
        $this->errors[] = $message;
    }

    private function socketAddress() : string
    {
        $scheme = $this->config['protocol'] ?  $this->config['protocol'] . '://' : null;

        return $scheme . $this->config['host'] . ':' . $this->config['port'];
    }
}
