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

namespace Origin\Mailer;

use Origin\Core\Config;
use Origin\Utility\Html;
use Origin\Utility\Inflector;
use Origin\Exception\Exception;
use Origin\Core\StaticConfigTrait;
use Origin\Exception\InvalidArgumentException;
use Origin\Mailer\Exception\MissingTemplateException;

class Email
{
    use StaticConfigTrait;

    const CRLF = "\r\n";

    protected $to = [];

    protected $from = [];

    protected $cc = [];

    protected $bcc = [];

    protected $replyTo = [];

    protected $sender = [];

    protected $returnPath = [];

    protected $subject = null;

    protected $charset = 'UTF-8';

    protected $htmlMessage = null;

    protected $textMessage = null;

    protected $headers = [];

    protected $messageId = null;

    protected $boundary = null;

    protected $attachments = [];

    protected $additionalHeaders = [];

    protected $encodeMessage = false;
    /**
     * This will be set automatically, e.g quoted-printable, base etc
     *
     * @var string
     */
    protected $encoding = null;

    /**
     * Email account config to use for sending email through this instance
     *
     * @var array
     */
    protected $account = null;

    protected $socket = null;
    /**
     * Holds the log for SMTP
     *
     * @var array
     */
    protected $smtpLog = [];

    /**
     * Template to use
     *
     * @var string
     */
    protected $template = null;

    /**
     * Email format.
     * The best practice is send both HTML and text
     *
     * @var string
     */
    protected $emailFormat = 'both';

    /**
     * Message object
     *
     * @var \Origin\Mailer\Message
     */
    protected $message = null;

    /**
     * Vars to be loaded in template
     *
     * @var array
     */
    protected $viewVars = [];

    /**
     * Constructor
     *
     * @param string|array|null $config config name, or array of settings
     */
    public function __construct($config = null)
    {
        if (extension_loaded('mbstring') === false) {
            throw new Exception('mbstring extension is not loaded');
        }
        $this->charset = Config::read('App.encoding');
        mb_internal_encoding($this->charset); // mb_list_encodings()

        if ($config === null) {
            $config = static::config('default');
        }
        if ($config) {
            $this->account($config);
        }
    }

    /**
     * Sets and gets the email account to be used by this email instance.
     * If a string is passed it will load from the config, if its an array it will create
     * a temporary config which can be only used by instance.
     *
     * Use this to create configs on the fly or switch from default config etc
     *
     * @param string|array $config
     * @return \Origin\Mailer\Email|array
     */
    public function account($config = null)
    {
        $account = null;

        if ($config === null) {
            return $this->account;
        }

        if (is_string($config)) {
            $account = $config;
            $config = static::config($config);
        }

        if (is_array($config)) {
            $defaults = [
                'host' => 'localhost',
                'port' => 25,
                'username' => null,
                'password' => null,
                'tls' => false,
                'ssl' => false,
                'domain' => null,
                'timeout' => 30,
                'engine' => 'Smtp'
            ];
            $this->account = array_merge($defaults, $config);
            $this->applyConfig();

            return $this;
        }

        throw new InvalidArgumentException(sprintf('The email account `%s` does not exist.', $account));
    }

    /**
     * Goes through the account config and passing data to certain function. When
     * setting up email in app for an account setting from all over the app can be
     * messy.
     *
     * @return void
     */
    protected function applyConfig() : void
    {
        foreach (['to', 'from', 'sender', 'replyTo'] as $method) {
            if (isset($this->account[$method])) {
                call_user_func_array([$this, $method], (array) $this->account[$method]);
            }
        }
        if (isset($this->account['bcc'])) {
            foreach ((array) $this->account['bcc'] as $email => $name) {
                if (is_int($email)) {
                    $email = $name;
                    $name = null;
                }
                $this->addBcc($email, $name);
            }
        }

        if (isset($this->account['cc'])) {
            foreach ((array) $this->account['cc'] as $email => $name) {
                if (is_int($email)) {
                    $email = $name;
                    $name = null;
                }
                $this->addCc($email, $name);
            }
        }
    }

    /**
     * To
     *
     * @param string $email
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function to(string $email, string $name = null) : Email
    {
        $this->setEmail('to', $email, $name);

        return $this;
    }

    /**
     * Add another to address
     *
     * @param string $email
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function addTo(string $email, string $name = null) : Email
    {
        $this->addEmail('to', $email, $name);

        return $this;
    }

    /**
     * Set a cc address
     *
     * @param string $email
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function cc(string $email, string $name = null) : Email
    {
        $this->setEmail('cc', $email, $name);

        return $this;
    }

    /**
     * Add another cc address
     *
     * @param string $email
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function addCc(string $email, string $name = null) : Email
    {
        $this->addEmail('cc', $email, $name);

        return $this;
    }

    /**
     * Sets the bcc
     *
     * @param string $email
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function bcc(string $email, string $name = null) : Email
    {
        $this->setEmail('bcc', $email, $name);

        return $this;
    }

    /**
     * Add another bcc address
     *
     * @param string $email
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function addBcc(string $email, string $name = null) : Email
    {
        $this->addEmail('bcc', $email, $name);

        return $this;
    }

    /**
     * Sets the email from
     *
     * @param string $email
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function from(string $email, string $name = null) : Email
    {
        $this->setEmailSingle('from', $email, $name);

        return $this;
    }

    /**
     * Sets the sender for the email
     *
     * @param string $email
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function sender(string $email, string $name = null) : Email
    {
        $this->setEmailSingle('sender', $email, $name);

        return $this;
    }

    /**
     * Sets the reply-to
     *
     * @param string $email
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function replyTo(string $email, string $name = null) : Email
    {
        $this->setEmailSingle('replyTo', $email, $name);

        return $this;
    }

    /**
     * Sets the return path
     *
     * @param string $email
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function returnPath(string $email, string $name = null) : Email
    {
        $this->setEmailSingle('returnPath', $email, $name);

        return $this;
    }

    /**
     * Sets the email subject
     *
     * @param string $subject
     * @return \Origin\Mailer\Email
     */
    public function subject(string $subject) : Email
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Sets the text version of email
     *
     * @param string $message
     * @return \Origin\Mailer\Email
     */
    public function textMessage(string $message) : Email
    {
        $this->textMessage = $message;

        return $this;
    }

    /**
     * Sets the html version of email
     *
     * @param string $message
     * @return \Origin\Mailer\Email
     */
    public function htmlMessage(string $message) : Email
    {
        $this->htmlMessage = $message;

        return $this;
    }

    /**
     * Sets the template to be loaded
     *
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function template(string $name) : Email
    {
        $this->template = $name;

        return $this;
    }

    /**
     * Sets the vars which will be set in template
     *
     * @param array $vars
     * @return \Origin\Mailer\Email
     */
    public function set(array $vars) : Email
    {
        $this->viewVars = $vars;

        return $this;
    }

    /**
     * Loads the email templates
     *
     * @param string $name
     * @return void
     */
    protected function loadTemplate(string $name) : void
    {
        list($plugin, $template) = pluginSplit($name);
        $path = SRC . DS . 'Http' . DS . 'View' . DS . 'Email';
        if ($plugin) {
            $path = PLUGINS . DS . Inflector::underscored($plugin) . DS . 'src' . DS . 'Http' . DS . 'View' . DS . 'Email';
        }

        if ($this->format() === 'html' or $this->format() === 'both') {
            $filename = $path . DS . 'html' . DS . $template . '.ctp';
            if (file_exists($filename) === false) {
                throw new MissingTemplateException(sprintf('Template %s not found', $filename));
            }
            $this->htmlMessage($this->renderTemplate($filename));
        }

        if ($this->format() === 'text' or $this->format() === 'both') {
            $filename = $path . DS . 'text' . DS . $template . '.ctp';
            if (file_exists($filename) === false) {
                if ($this->format() === 'both') {
                    $this->textMessage = Html::toText($this->htmlMessage);
                } else {
                    throw new MissingTemplateException(sprintf('Template %s not found', $filename));
                }
            } else {
                $this->textMessage($this->renderTemplate($filename));
            }
        }
    }

    /**
     * Handles the rendering of the email template
     *
     * @param string $template__filename
     * @return string
     */
    protected function renderTemplate(string $template__filename) : string
    {
        extract($this->viewVars);

        ob_start();

        include $template__filename;

        return ob_get_clean();
    }

    /**
     * Add a custom header to the email message
     *
     * @param string $name
     * @param string $value
     * @return \Origin\Mailer\Email
     */
    public function addHeader(string $name, string $value) : Email
    {
        $this->additionalHeaders[$name] = $value;

        return $this;
    }

    /**
     * Adds an attachment
     *
     * @param string $filename
     * @param string $name
     * @return \Origin\Mailer\Email
     */
    public function addAttachment(string $filename, string $name = null) : Email
    {
        if ($name == null) {
            $name = basename($filename);
        }
        if (file_exists($filename)) {
            $this->attachments[$filename] = $name;

            return $this;
        }
        throw new Exception($filename . ' not found');
    }

    /**
     * Adds multiple attachments
     *
     * @param array $attachments ['/tmp/filename','/images/logo.png'=>'Your Logo.png']
     * @return \Origin\Mailer\Email
     */
    public function addAttachments(array $attachments) : Email
    {
        foreach ($attachments as $filename => $name) {
            if (is_int($filename)) {
                $filename = $name;
                $name = null;
            }
            $this->addAttachment($filename, $name);
        }

        return $this;
    }

    /**
     * Undocumented function
     *
     * @param string $message Text message if sending directly like this
     * @return \Origin\Mailer\Message
     */
    public function send(string $message = null) : Message
    {
        if (empty($this->from)) {
            throw new Exception('From email is not set.');
        }

        if (empty($this->to)) {
            throw new Exception('To email is not set.');
        }

        if ($message) {
            $this->format('text');
            $this->textMessage = $message;
        }

        if ($this->template) {
            $this->loadTemplate($this->template);
        }

        if (($this->format() === 'html' or $this->format() === 'both') and empty($this->htmlMessage)) {
            throw new Exception('Html Message not set.');
        }

        if (($this->format() === 'text' or $this->format() === 'both') and empty($this->textMessage)) {
            if ($this->format() === 'both') {
                $this->textMessage = Html::toText($this->htmlMessage);
            } else {
                throw new Exception('Text Message not set.');
            }
        }

        if ($this->account === null) {
            throw new Exception('Email config has not been set.');
        }
       
        $this->message = $this->render();
       
        if ($this->account['engine'] === 'Smtp') {
            $this->smtpSend();
        }

        return $this->message;
    }

    /**
     * Builds the headers and message
     *
     * @return \Origin\Mailer\Message
     */
    protected function render() : message
    {
        $headers = '';
        foreach ($this->buildHeaders() as $header => $value) {
            $headers .= "{$header}: {$value}" . self::CRLF;
        }
        $message = implode(self::CRLF, $this->buildMessage());
        $message = new Message($headers, $message);

        return $message;
    }

    /**
     * Sends the message through SMTP
     *
     * @return void
     */
    protected function smtpSend() : void
    {
        $account = $this->account;

        $this->openSocket($account);
        $this->connect($account);

        $this->authenticate($account);

        $this->sendCommand('MAIL FROM: <' . $this->from[0] . '>', '250');
        $recipients = array_merge($this->to, $this->cc, $this->bcc);
        foreach ($recipients as $recipient) {
            $this->sendCommand('RCPT TO: <' . $recipient[0] . '>', '250|251');
        }

        $this->sendCommand('DATA', '354');

        $this->sendCommand($this->message . self::CRLF . self::CRLF . self::CRLF . '.', '250');

        $this->sendCommand('QUIT', '221');

        $this->closeSocket();
    }

    /**
     * Handles the STMP authentication
     *
     * @param array $account
     * @return void
     */
    protected function authenticate(array $account) : void
    {
        if (isset($account['username']) and isset($account['password'])) {
            $this->sendCommand('AUTH LOGIN', '334');
            $this->sendCommand(base64_encode($account['username']), '334');
            $this->sendCommand(base64_encode($account['password']), '235');
        }
    }

    /**
     * Connects to the SMTP server
     *
     * @param array $account
     * @return void
     */
    protected function connect(array $account) : void
    {
        $this->sendCommand(null, '220');

        /**
         * The argument field contains the fully-qualified domain name of the SMTP client if one is available.
         * In situations in which the SMTP client system does not have a meaningful domain name (e.g., when its
         * address is dynamically allocated and no reverse mapping record is available), the client SHOULD send
         * an address literal (see section 4.1.3), optionally followed by information that will help to identify
         * the client system. Address literal is [192.0.2.1]
         * @see http://www.ietf.org/rfc/rfc2821.txt
         */
        $domain = '[127.0.0.1]';
        if (isset($account['domain'])) {
            $domain = $account['domain'];
        }
        
        $this->sendCommand("EHLO {$domain}", '250');
        if ($account['tls']) {
            $this->sendCommand('STARTTLS', '220');
            if (stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) === false) {
                throw new Exception('The server did not accept the TLS connection.');
            }
            $this->sendCommand("EHLO {$domain}", '250');
        }
    }

    /**
     * Checks if the socket is opened
     *
     * @return boolean
     */
    protected function isConnected() : bool
    {
        return is_resource($this->socket);
    }

    /**
     * Sends a command to the socket and waits for a response.
     *
     * @param null,string $data
     * @param string $code
     * @return string $code
     */
    protected function sendCommand(string $data = null, $code = '250') : string
    {
        if ($data != null) {
            $this->socketLog($data);
            fputs($this->socket, $data . self::CRLF);
        }
        $response = '';
        $startTime = time();
        while (is_resource($this->socket) and ! feof($this->socket)) {
            $buffer = @fgets($this->socket, 515);
            $this->socketLog(rtrim($buffer));
            $response .= $buffer;

            /**
             * RFC5321 S4.1 + S4.2
             * @see https://tools.ietf.org/html/rfc5321
             * Stop if 4 character is space or response is only 3 characters (not valid must be handled
             * according to standard)
             */

            if (substr($buffer, 3, 1) == ' ' or strlen($buffer) === 3) {
                break;
            }

            $info = stream_get_meta_data($this->socket);
            if ($info['timed_out'] or (time() - $startTime) >= $this->account['timeout']) {
                throw new Exception('SMTP timeout.');
            }
        }

        $responseLines = explode(self::CRLF, rtrim($response, self::CRLF));
        $lastResponse = end($responseLines);

        if (preg_match("/^($code)/", $lastResponse)) {
            return $code; // Return response code
        }

        throw new Exception(sprintf('SMTP Error: %s', $response));
    }

    /**
     * Adds message to the SMTP log
     *
     * @param string $data
     * @return void
     */
    protected function socketLog(string $data) : void
    {
        $this->smtpLog[] = $data;
    }

    /**
     * Opens a Socket or throws an exception
     *
     * @param array $account
     * @return void
     */
    protected function openSocket(array $account, array $options = []) : void
    {
        $protocol = 'tcp';
        if ($account['ssl']) {
            $protocol = 'ssl';
        }
        $server = $protocol . '://' . $account['host'] . ':' . $account['port'];
        $this->socketLog('Connecting to ' . $server);

        set_error_handler([$this, 'connectionErrorHandler']);
        $this->socket = stream_socket_client(
            $server,
            $errorNumber,
            $errorString,
            $account['timeout'],
            STREAM_CLIENT_CONNECT,
            stream_context_create($options)
        );
        restore_error_handler();

        if (! $this->isConnected()) {
            $this->socketLog('Unable to connect to the SMTP server.');
            throw new Exception('Unable to connect to the SMTP server.');
        }
        $this->socketLog('Connected to SMTP server.');

        /**
         * Does not time out just an array returned by stream_get_meta_data() with the key timed_out
         */
        stream_set_timeout($this->socket, $this->account['timeout']); // Sets a timeouted key
    }

    /**
     * This is the error handler when opening the stream
     *
     * @param int $code
     * @param string $message
     * @return void
     */
    protected function connectionErrorHandler(int $code, string $message) : void
    {
        $this->smtpLog[] = $message;
    }

    /**
     * Closes the socket
     *
     * @return void
     */
    protected function closeSocket() : void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

    /**
     * Returns the smtp log
     *
     * @return array
     */
    public function smtpLog() : array
    {
        return $this->smtpLog;
    }

    protected function setEmail(string $var, string $email = null, string $name = null) : void
    {
        $this->{$var} = [];
        $this->addEmail($var, $email, $name);
    }

    protected function addEmail(string $var, string $email = null, string $name = null) : void
    {
        $this->validateEmail($email);
        $this->{$var}[] = [$email, $name];
    }

    protected function setEmailSingle(string $var, string $email = null, string $name = null) : void
    {
        $this->validateEmail($email);
        $this->{$var} = [$email, $name];
    }

    /**
     * Validates an email
     *
     * @internal this validation process also checks for newlines which is important in email header injection attacks
     * @param string $email
     * @return bool
     * @throws Exception
     */
    protected function validateEmail($email) : bool
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        throw new Exception(sprintf('Invalid email address %s', $email));
    }

    /**
     * Validates a header line to prevent Email Header Injections
     *
     * @param string $input
     * @return bool
     */
    protected function validateHeader(string $input = null) : bool
    {
        return ($input === str_ireplace(["\r", "\n", '%0A', '%0D'], '', $input));
    }

    /**
     * Builds an array of headers for the email
     *
     * @return array
     */
    protected function buildHeaders() : array
    {
        $headers = [];

        $headers['MIME-Version'] = '1.0';
        $headers['Date'] = date('r'); //RFC 2822
        $headers['Message-ID'] = $this->getMessageId();

        foreach ($this->additionalHeaders as $header => $value) {
            $headers[$header] = $value;
        }

        $headers['Subject'] = $this->getSubject();

        $optionals = ['sender' => 'Sender', 'replyTo' => 'Reply-To', 'returnPath' => 'Return-Path'];
        foreach ($optionals as $var => $header) {
            if ($this->{$var}) {
                $headers[$header] = $this->formatAddress($this->{$var});
            }
        }

        $headers['From'] = $this->formatAddress($this->from);

        foreach (['to', 'cc', 'bcc'] as $var) {
            if ($this->{$var}) {
                $headers[ucfirst($var)] = $this->formatAddresses($this->{$var});
            }
        }

        /**
        * Look for Email Header Injection
        */
        foreach ($headers as $header) {
            if (! $this->validateHeader($header)) {
                throw new Exception(sprintf('Possible Email Header Injection `%s`', $header));
            }
        }

        $headers['Content-Type'] = $this->getContentType();
        if ($this->needsEncoding() and empty($this->attachments) and $this->format() !== 'both') {
            $headers['Content-Transfer-Encoding'] = 'quoted-printable';
        }

        return $headers;
    }

    /**
     * Builds the message array
     *
     * @return array message
     */
    protected function buildMessage() : array
    {
        $message = [];

        $emailFormat = $this->format();
        $needsEncoding = $this->needsEncoding();
        $altBoundary = $boundary = $this->getBoundary();

        if ($this->attachments and ($emailFormat === 'html' or $emailFormat === 'text')) {
            $message[] = '--' . $boundary;
            if ($emailFormat == 'text') {
                $message[] = 'Content-Type: text/plain; charset="' . $this->charset . '"';
            }
            if ($emailFormat == 'html') {
                $message[] = 'Content-Type: text/html; charset="' . $this->charset . '"';
            }
            
            if ($needsEncoding) {
                $message[] = 'Content-Transfer-Encoding: quoted-printable';
            }
            $message[] = '';
        }

        if ($this->attachments and $emailFormat == 'both') {
            $altBoundary = 'alt-' . $boundary;
            $message[] = '--' . $boundary;
            $message[] = 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"';
            $message[] = '';
        }

        if (($emailFormat === 'text' or $emailFormat ==='both') and $this->textMessage) {
            if ($emailFormat == 'both') {
                $message[] = '--' . $altBoundary;
                $message[] = 'Content-Type: text/plain; charset="' . $this->charset . '"';
                if ($needsEncoding) {
                    $message[] = 'Content-Transfer-Encoding: quoted-printable';
                }
                $message[] = '';
            }
            $message[] = $this->formatMessage($this->textMessage, $needsEncoding);
            $message[] = '';
        }

        if (($emailFormat === 'html' or $emailFormat ==='both') and $this->htmlMessage) {
            if ($emailFormat == 'both') {
                $message[] = '--' . $altBoundary;
                $message[] = 'Content-Type: text/html; charset="' . $this->charset . '"';
                if ($needsEncoding) {
                    $message[] = 'Content-Transfer-Encoding: quoted-printable';
                }
                $message[] = '';
            }
            $message[] = $this->formatMessage($this->htmlMessage, $needsEncoding);
            $message[] = '';
        }

        if ($this->attachments) {
            foreach ($this->attachments as $filename => $name) {
                $mimeType = mime_content_type($filename);
                $message[] = '--' . $boundary;
                $message[] = "Content-Type: {$mimeType}; name=\"{$name}\"";
                $message[] = 'Content-Disposition: attachment';
                $message[] = 'Content-Transfer-Encoding: base64';
                $message[] = '';
                $message[] = chunk_split(base64_encode(file_get_contents($filename)));
                $message[] = '';
            }
        }
        if ($emailFormat == 'both' or $this->attachments) {
            $message[] = '--' . $boundary . '--';
        }

        return $message;
    }

    /**
     * Standardizes the line endings and encodes if needed
     *
     * @param string $message
     * @return string
     */
    protected function formatMessage(string $message, bool $needsEncoding) : string
    {
        $message = preg_replace("/\r\n|\n/", "\r\n", $message);
        if ($needsEncoding) {
            $message = quoted_printable_encode($message);
        }

        return $message;
    }

    /**
     * Gets the message id for the message
     * if messageId is null [default] then it generates a UUID
     *
     * @return string
     */
    protected function getMessageId() : string
    {
        if ($this->messageId === null) {
            /**
             * Generate random UUID.
             * @see http://tools.ietf.org/html/rfc4122#section-4.4
             * These need to be changed
             * time_hi_and_version (bits 4-7 of 7th octet),
             * clock_seq_hi_and_reserved (bit 6 & 7 of 9th octet)
             */
            $bytes = random_bytes(16);
            $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // set version to 0100
            $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // set bits 6-7 to 10
            $this->messageId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        }

        return $this->messageId . '@' . $this->getDomain();
    }

    /**
     * Gets the domain to be used for message id generation
     * @return string
     */
    public function getDomain() : string
    {
        $domain = php_uname('n');
        if ($this->from) {
            $email = $this->from[0];
            list(, $domain) = explode('@', $email);
        }

        return $domain;
    }

    /**
     * Gets the boundary to be used in the email, if not set it will generate a unique id
     *
     * @return string
     */
    protected function getBoundary() : string
    {
        if ($this->boundary === null) {
            $this->boundary = md5(random_bytes(16));
        }

        return $this->boundary;
    }

    /**
     * Gets and encodes the subject
     *
     * @return string
     */
    protected function getSubject() : string
    {
        if ($this->subject) {
            $this->subject = mb_encode_mimeheader($this->subject, $this->charset, 'B');
        }

        return $this->subject;
    }

    /**
     * Gets the content type for the email
     *
     * @return string
     */
    protected function getContentType() : string
    {
        if ($this->attachments) {
            return 'multipart/mixed; boundary="' . $this->getBoundary() . '"';
        }
        $emailFormat = $this->format();

        if ($emailFormat === 'both') {
            return 'multipart/alternative; boundary="' . $this->getBoundary() . '"';
        }
        if ($emailFormat === 'html') {
            return 'text/html; charset="' . $this->charset . '"';
        }

        return 'text/plain; charset="' . $this->charset . '"';
    }

    /**
     * Gets/Sets the email format
     *
     * @param string|null $type html, text or both
     * @return string|\Origin\Mailer\Email
     */
    public function format($format = null)
    {
        if ($format === null) {
            return $this->emailFormat;
        }
        if (! in_array($format, ['text', 'html', 'both'])) {
            throw new InvalidArgumentException('Invalid email format');
        }
        $this->emailFormat = $format;

        return $this;
    }
    /**
     * Checks if a message needs to be encoded
     *
     * @return bool
     */
    protected function needsEncoding() : bool
    {
        $emailFormat = $this->format();
        
        if (($emailFormat == 'text' or $emailFormat == 'both') and mb_check_encoding($this->textMessage, 'ASCII') === false) {
            return true;
        }
        if (($emailFormat == 'html' or $emailFormat == 'both') and mb_check_encoding($this->htmlMessage, 'ASCII') === false) {
            return true;
        }

        return false;
    }

    /**
     * Returns a formatted address
     *
     * @param array $address
     * @return string james@originphp.com James <james@originphp.com>
     */
    protected function formatAddress(array $address) : string
    {
        list($email, $name) = $address;
        if ($name === null) {
            return $email;
        }
        $name = mb_encode_mimeheader($name, $this->charset, 'B');

        return "{$name} <{$email}>";
    }

    /**
     * Formats multiple email addresses to be used within email headers
     *
     * @param array $addresses
     * @return string
     */
    protected function formatAddresses(array $addresses) : string
    {
        $result = [];
        foreach ($addresses as $address) {
            $result[] = $this->formatAddress($address);
        }

        return implode(', ', $result);
    }
}
