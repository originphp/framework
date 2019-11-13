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
    private $counter = 0;

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
            'host' => null,'port' => 143,'protocol' => 'imap','encryption' => null,'validateCert' => true,
            'username' => null, 'password' => null,'timeout' => 30
        ];
        
        if (! in_array($config['protocol'], ['pop3','imap'])) {
            throw new InvalidArgumentException('Only pop3 and imap supported');
        }
        if ($config['encryption'] and ! in_array($config['encryption'], ['ssl','tls','notls'])) {
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
    private function connect() : void
    {
        $errorNo = $errorMessage = null;
        set_error_handler(function ($e, $m) use (&$errorNo,&$errorMessage) {
            $errorNo = $e;
            $errorMessage = $m;
        });
        try {
            $this->connection = imap_open(
                $this->connectionString(),
                $this->config['username'],
                $this->config['password']
             );
        } catch (\Exception $ex) {
        }
        restore_error_handler();

        ini_restore('default_socket_timeout');
   
        imap_errors();
        imap_alerts();
         
        if (! $this->connection) {
            throw new Exception('Error connecting to ' . $this->config['host'] .':' . $this->config['port']);
        }
    }

    /**
     * Gets the count of the messages downloaded
     *
     * @return integer
     */
    public function count() : int
    {
        return $this->counter;
    }

    /**
     * Download messages, this will return a Generator which use a foreach loop on even
     * if it has no results. Generators do not implement countable.
     *
     * @param array $options Options support the following keys :
     *  - limit:
     * @return \Generator|null
     */
    public function download(array $options = []) : ?Generator
    {
        $options += ['limit' => 0,'messageId' => null];

        $this->connect();
    
        # Download the messages
        if ($options['messageId']) {
            $out = $this->downloadSync($options);
        } else {
            $out = $this->downloadStandard($options);
        }
        
        # Shutdown
        imap_errors();
        imap_alerts();
      
        imap_close($this->connection);
        $this->connection = null;

        return $out ? $this->generator($out) : null;
    }

    /**
     * Downloads messages in reverse order first since messages are stored on server and starting from
     * top means each time we have to check emails that could be existing from years ago. Once the messages
     * are downloaded it then reverses the array so they are back in the order they were received.
     * Using the messageID, you can sync to last message that you downloaded
     *
     * @param array $options
     * @return array
     */
    private function downloadSync(array $options) : array
    {
        $count = imap_num_msg($this->connection);

        $out = [];
        
        # Loop backwards for IMAP
        for ($i = $count; $i > 0;$i--) {
            $header = imap_headerinfo($this->connection, $i);
            if ($options['messageId'] === $header->message_id) {
                break;
            }
            $out[] = $this->saveMessage($i);
            $this->counter++;
            if ($options['limit'] and $options['limit'] === $this->counter) {
                break;
            }
        }
       
        return array_reverse($out);
    }

    /**
     * Downloads messages (first download for IMAP or download for POP3)
     *
     * @param array $options
     * @return array
     */
    private function downloadStandard(array $options) : array
    {
        $count = imap_num_msg($this->connection);

        $out = [];
        # Loop forward for POP
        for ($i = 1; $i <= $count; $i++) {
            $out[] = $this->saveMessage($i);
            $this->counter++;
            if ($options['limit'] and $options['limit'] === $this->counter) {
                break;
            }
        }

        return $out;
    }

    /**
     * Saves the message to a temp file
     *
     * @param integer $messageId
     * @return string
     */
    private function saveMessage(int $messageId) : string
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'f');
        $fh = fopen($tmpfile, 'w');
        fwrite($fh, imap_fetchbody($this->connection, $messageId, ''));
        fclose($fh);

        return $tmpfile;
    }

    /**
     * Creates the generator from the results
     *
     * @param array $out
     * @return \Generator
     */
    private function generator(array $out) : Generator
    {
        foreach ($out as $tmpfile) {
            $message = file_get_contents($tmpfile);
            unlink($tmpfile);
            yield $message;
        }
    }

    /**
     * Creates the connection string
     *
     * @return string
     */
    private function connectionString() : string
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
}
