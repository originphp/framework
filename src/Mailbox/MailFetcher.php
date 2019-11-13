<?php
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
declare(strict_types=1);
namespace Origin\Mailbox;

use Generator;
use Origin\Core\Exception\Exception;
use Origin\Core\Exception\InvalidArgumentException;

class MailFetcher
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @var Resource
     */
    private $connection;

    /**
     * Number of messages downloaded
     *
     * @var integer
     */
    private $count = 0;

    /**
     * Constructor
     *
     * @param array $config The following configuration keys
     *  - host: hostname or ip address
     *  - port: default: 143
     *  - protocol: imap or pop3
     *  - encryption: ssl, tls, or nontls
     *  - validateCert: default:true set to false to not check the SSL cert
     */
    public function __construct(array $config)
    {
        $config += [
            'host' => null, 'port' => 143, 'protocol' => 'imap', 'encryption' => null, 'validateCert' => true,
            'username' => null, 'password' => null, 'timeout' => 30
        ];

        if (! in_array($config['protocol'], ['pop3', 'imap'])) {
            throw new InvalidArgumentException('Only pop3 and imap supported');
        }
        if ($config['encryption'] and ! in_array($config['encryption'], ['ssl', 'tls', 'notls'])) {
            throw new InvalidArgumentException('Invalid encryption type');
        }

        $this->config = $config;

        # Set timeout
        imap_timeout(IMAP_OPENTIMEOUT, $this->config['timeout']);
        imap_timeout(IMAP_READTIMEOUT, $this->config['timeout']);
        imap_timeout(IMAP_WRITETIMEOUT, $this->config['timeout']);
        imap_timeout(IMAP_CLOSETIMEOUT, $this->config['timeout']);

        # Connect
        ini_set('default_socket_timeout', (string) $this->config['timeout']);
    }
    /**
     * Connects to the imap/pop server
     *
     * @return void
     */
    private function connect(): void
    {
        /**
         * Register error handler to catch errors
         */
        $errorNo = $errorMessage = null;
        set_error_handler(function ($e, $m) use (&$errorNo, &$errorMessage) {
            $errorNo = $e;
            $errorMessage = $m;
        });

        $this->connection = imap_open($this->connectionString(), $this->config['username'], $this->config['password']);

        restore_error_handler();
        ini_restore('default_socket_timeout');

        if (! $this->connection or $errorMessage) {
            throw new Exception('Error connecting to ' . $this->config['host'] . ':' . $this->config['port']);
        }
    }

    /**
     * Download messages. The actual messages themselves are downloaded during the for loop iteration. If you are
     * syncing an IMAP mailbox, there will be small delay first.
     *
     * @param array $options Options support the following keys :
     *  - limit: max number of messages to download
     *  - messageId: last message downloaded by IMAP. This will trigger mailbox SYNC
     * @return \Generator
     */
    public function download(array $options = []): Generator
    {
        $options += ['limit' => 0, 'messageId' => null];

        $this->connect();

        $emails = imap_search($this->connection, 'ALL');

        # If IMAP last message
        if ($options['messageId']) {
            krsort($emails);
            $emails = $this->mailboxSync($emails, $options['messageId']);
        }

        $emails = $emails ? $emails : [];
        $this->count = count($emails);

        if ($options['limit'] and count($emails) > $options['limit']) {
            $emails = array_slice($emails, -$options['limit']);
        }

        return $this->generator($emails);
    }

    /**
     * Syncs an IMAP mailbox, by looping through list of emails (in reverse order) then
     * when it finds the message ID it stops.
     *
     * @param array $emails
     * @param string $messageId
     * @return array
     */
    private function mailboxSync(array $emails, string $messageId) : array
    {
        $out = [];
        # Get messages to be fetched
        foreach ($emails as $id) {
            $header = imap_headerinfo($this->connection, $id);
            if ($messageId === $header->message_id) {
                break;
            }
            $out[] = $id;
        }

        return array_reverse($out);
    }

    /**
     * Creates the generator which downloads emails
     *
     * @param array $emails
     * @return \Generator
     */
    private function generator(array $emails): Generator
    {
        foreach ($emails as $msgnumber) {
            yield imap_fetchbody($this->connection, $msgnumber, '');
        }
        $this->disconnect();
    }

    /**
     * Disconnects a connection
     *
     * @return boolean
     */
    private function disconnect() : bool
    {
        if ($this->connection) {
            imap_errors();
            imap_alerts();
            imap_close($this->connection);
            $this->connection = null;

            return true;
        }

        return false;
    }

    /**
     * Gets the count of the messages downloaded
     *
     * @return integer
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * Creates the connection string
     *
     * @return string
     */
    private function connectionString(): string
    {
        $args = [
            $this->config['host'] . ':' . $this->config['port'],
            $this->config['protocol']
        ];

        if ($this->config['encryption']) {
            $args[] = $this->config['encryption'];
        }

        if ($this->config['validateCert'] === false) {
            $args[] = 'novalidate-cert';
        }

        $mailbox = $this->config['protocol'] === 'imap' ? 'INBOX' : null;

        return '{' . implode('/', $args) . '}' . $mailbox;
    }

    public function __destruct()
    {
        if ($this->connection) {
            $this->disconnect();
        }
    }
}
