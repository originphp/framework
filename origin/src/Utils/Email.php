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

namespace Origin\Utils;

use Origin\Exception\Exception;
use Origin\Core\Configure;
use Origin\Core\Inflector;
use Origin\Utils\Exception\MissingTemplateException;
use Origin\Core\StaticConfigTrait;

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

    protected $textMessage =  null;

    protected $headers = [];

    protected $messageId = null;
    
    protected $boundary = null;

    protected $attachments =[];

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

    protected $emailFormat = 'text';

    /**
     * This is the headrers + body
     *
     * @var array
     */
    protected $content = null;

    /**
     * Vars to be loaded in template
     *
     * @var array
     */
    protected $viewVars = [];

    public function __construct($config = null)
    {
        if (extension_loaded('mbstring') === false) {
            throw new Exception('mbstring extension is not loaded');
        }
        $this->charset = Configure::read('App.encoding');
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
     * @return void
     */
    public function account($config = null)
    {
        if ($config === null) {
            return $this->account;
        }

        if (is_string($config)) {
            $config = static::config($config);
        }

        if (is_array($config)) {
            $defaults = [
                'host'=>'localhost',
                'port'=>25,
                'username'=>null,
                'password' => null,
                'tls'=>false,
                'client'=>null,
                'timeout'=>30
            ];
            $this->account = array_merge($defaults, $config);
            $this->applyConfig();
            return $this;
        }
        
        throw new Exception(sprintf('Unkown email configuration %s', $config));
    }

    /**
     * Goes through the account config and passing data to certain function. When
     * setting up email in app for an account setting from all over the app can be
     * messy.
     *
     * @return void
     */
    protected function applyConfig()
    {
        $methods = ['to','from','sender','bcc','cc','replyTo'];
        foreach ($this->account as $method => $args) {
            if (in_array($method, $methods)) {
                call_user_func_array([$this,$method], (array) $args);
            }
        }
    }

    /**
     * To
     *
     * @param string $email
     * @param string $name
     * @return void
     */
    public function to(string $email, string $name = null)
    {
        $this->setEmail('to', $email, $name);
        return $this;
    }

    /**
     * Add another to address
     *
     * @param string $email
     * @param string $name
     * @return void
     */
    public function addTo(string $email, string $name = null)
    {
        $this->addEmail('to', $email, $name);
        return $this;
    }

    /**
     * cc
     *
     * @param string $email
     * @param string $name
     * @return void
     */
    public function cc(string $email, string $name = null)
    {
        $this->setEmail('cc', $email, $name);
        return $this;
    }

    /**
     * Add another cc address
     *
     * @param string $email
     * @param string $name
     * @return void
     */
    public function addCc(string $email, string $name = null)
    {
        $this->addEmail('cc', $email, $name);
        return $this;
    }

    /**
     * bcc
     *
     * @param string $email
     * @param string $name
     * @return void
     */
    public function bcc(string $email, string $name = null)
    {
        $this->setEmail('bcc', $email, $name);
        return $this;
    }

    /**
     * Add another cc address
     *
     * @param string $email
     * @param string $name
     * @return void
     */
    public function addBcc(string $email, string $name = null)
    {
        $this->addEmail('bcc', $email, $name);
        return $this;
    }

    /**
     * From
     *
     * @param string $email
     * @param string $name
     * @return void
     */
    public function from(string $email, string $name = null)
    {
        $this->setEmailSingle('from', $email, $name);
        return $this;
    }

    /**
    * Sender
    *
    * @param string $email
    * @param string $name
    * @return void
    */
    public function sender(string $email, string $name = null)
    {
        $this->setEmailSingle('sender', $email, $name);
        return $this;
    }

    /**
     * Reply To
     *
     * @param string $email
     * @param string $name
     * @return void
     */
    public function replyTo(string $email, string $name = null)
    {
        $this->setEmailSingle('replyTo', $email, $name);
        return $this;
    }

    /**
     * Return Path
     *
     * @param string $email
     * @param string $name
     * @return void
     */
    public function returnPath(string $email, string $name = null)
    {
        $this->setEmailSingle('returnPath', $email, $name);
        return $this;
    }

    /**
     * Sets the subject
     *
     * @param string $subject
     * @return void
     */
    public function subject(string $subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Sets the text version of email
     *
     * @param string $message
     * @return void
     */
    public function textMessage(string $message)
    {
        $this->textMessage = $message;
        return $this;
    }


    /**
     * Sets the html version of email
     *
     * @param string $message
     * @return void
     */
    public function htmlMessage(string $message)
    {
        $this->htmlMessage = $message;
        return $this;
    }

    /**
     * Sets the template to be loaded
     *
     * @param string $name
     * @return void
     */
    public function template(string $name)
    {
        $this->template = $name;
        
        return $this;
    }

    /**
     * Sets the vars which will be set in temploate
     *
     * @param array $vars
     * @return void
     */
    public function setVars(array $vars)
    {
        $this->viewVars = $vars;
        return $this;
    }

    protected function loadTemplate(string $name)
    {
        list($plugin, $template) = pluginSplit($name);
        $path = VIEW . DS . 'Email';
        if ($plugin) {
            $path = PLUGINS . DS . Inflector::underscore($plugin) . DS . 'src' . DS . 'View'. DS . 'Email';
        }
        if ($this->format() === 'text' or $this->format() ==='both') {
            $filename = $path . DS . 'text' . DS . $template . '.ctp';
            if (file_exists($filename) === false) {
                throw new MissingTemplateException(sprintf('Template %s not found', $filename));
            }
            $this->textMessage($this->renderTemplate($filename));
        }
        if ($this->format() === 'html' or $this->format() ==='both') {
            $filename = $path . DS . 'html' . DS . $template . '.ctp';
            if (file_exists($filename) === false) {
                throw new MissingTemplateException(sprintf('Template %s not found', $filename));
            }
            $this->htmlMessage($this->renderTemplate($filename));
        }
    }
    
    protected function renderTemplate(string $template__filename)
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
     * @return void
     */
    public function addHeader(string $name, string $value)
    {
        $this->additionalHeaders[$name] = $value;
        return $this;
    }

    /**
     * Adds an attachment
     *
     * @param string $filename
     * @param string $name
     * @return void
     */
    public function addAttachment(string $filename, string $name = null)
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
     * @return void
     */
    public function addAttachments(array $attachments)
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

    public function send($content = null)
    {
        if (empty($this->from)) {
            throw new Exception('From email is not set.');
        }
        
        if (empty($this->to)) {
            throw new Exception('To email is not set.');
        }

        if ($content) {
            $this->textMessage = $content;
        }
        
        if ($this->template) {
            $this->loadTemplate($this->template);
        }
        
        if (($this->format() ==='text' or $this->format() ==='both') and empty($this->textMessage)) {
            throw new Exception('Text Message not set.');
        }

        if (($this->format() ==='html' or $this->format() ==='both') and empty($this->htmlMessage)) {
            throw new Exception('Html Message not set.');
        }

        if ($this->account === null) {
            throw new Exception('Email config has not been set.');
        }

        $this->content = $this->render();

        if (!isset($this->account['debug']) or $this->account['debug']) {
            $this->smtpSend();
        }

        return $this->content;
    }
    
    protected function render()
    {
        $headers = '';
        foreach ($this->buildHeaders() as $header => $value) {
            $headers .= "{$header}: {$value}" . self::CRLF;
        }
        $message = implode(self::CRLF, $this->buildMessage());
        
        return $headers . self::CRLF . self::CRLF . $message;
    }

    /**
     * Sends the actual message
     *
     * @return string headers + message
     */
    protected function smtpSend()
    {
        $account = $this->account;
        $account['protocol'] = 'tcp';
        if (strpos($account['host'], '://') !== false) {
            list($account['protocol'], $account['host']) = explode('://', $account['host']);
        }
     
        $this->openSocket($account);
        $this->connect($account);

        $this->authenticate($account);

        $this->sendCommand('MAIL FROM: <' . $this->from[0] . '>', '250');
        $recipients = array_merge($this->to, $this->cc, $this->bcc);
        foreach ($recipients as $recipient) {
            $this->sendCommand('RCPT TO: <' . $recipient[0] . '>', '250|251');
        }

        $this->sendCommand('DATA', '354');
        
        $this->sendCommand($this->content . self::CRLF.self::CRLF.self::CRLF .'.', '250');
       
        $this->sendCommand('QUIT', '221');
       
        $this->closeSocket();
    }

    protected function authenticate($account)
    {
        if (isset($account['username']) and isset($account['password'])) {
            $this->sendCommand("AUTH LOGIN", '334');
            $this->sendCommand(base64_encode($account['username']), '334');
            $this->sendCommand(base64_encode($account['password']), '235');
        }
    }

    protected function connect(array $account)
    {
        $this->sendCommand(null, '220');

        $host = 'localhost';
        if (isset($account['client'])) {
            $host = $account['client'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            list($host, $port) = explode(':', $_SERVER['HTTP_HOST']);
        }
        $this->sendCommand("EHLO {$host}", '250');
        if ($account['tls']) {
            $this->sendCommand("STARTTLS", '220');
            if (stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) === false) {
                throw new Exception('The server did not accept the TLS connection.');
            }
            $this->sendCommand("EHLO {$host}", '250');
        }
    }


    protected function isConnected()
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
    protected function sendCommand(string $data = null, $code = '250')
    {
        if ($data != null) {
            $this->socketWrite($data);
        }
        $response = '';
        $startTime = time();
        while (is_resource($this->socket) and !feof($this->socket)) {
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
        
        if (preg_match("/^($code)/i", $response)) {
            return $code; // Return response code
        }
        
        throw new exception(sprintf('SMTP Error: %s', $response));
    }

    protected function socketWrite(string $data)
    {
        if (!$this->isConnected()) {
            return false;
        }
        $this->socketLog($data);
        return fputs($this->socket, $data . self::CRLF);
    }

    protected function socketLog(string $data)
    {
        //fwrite(STDERR, $data);
        $this->smtpLog[] = $data;
    }

    /**
     * Opens a Socket or throws an exception
     *
     * @param array $account
     * @return void
     */
    protected function openSocket(array $account, array $options=[])
    {
        set_error_handler([$this, 'connectionErrorHandler']);
        $server =  $account['protocol'] . '://' . $account['host'] . ':' . $account['port'];
        $this->socketLog('Connecting to ' . $server);
        $this->socket = stream_socket_client(
                $server,
                $errorNumber,
                $errorString,
                $account['timeout'],
                STREAM_CLIENT_CONNECT,
                stream_context_create($options)
            );
        restore_error_handler();
 
        if (!$this->isConnected()) {
            $this->socketLog('Unable to connect to the SMTP server.');
            throw new Exception('Unable to connect to the SMTP server.');
        }
        $this->socketLog('Connected to SMTP server.');
     
        /**
         * Does not time out just an array returned by stream_get_meta_data() with the key timed_out
         */
        stream_set_timeout($this->socket, $this->account['timeout']); // Sets a timeouted key
    }

    protected function connectionErrorHandler($code, $message)
    {
        $this->smtpLog[] = $message;
    }

    protected function closeSocket()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

    /**
     * Returns the smtp log
     *
     * @return void
     */
    public function smtpLog()
    {
        return $this->smtpLog;
    }

    protected function setEmail(string $var, string $email = null, string $name = null)
    {
        $this->{$var} = [];
        $this->addEmail($var, $email, $name);
    }

    protected function addEmail(string $var, string $email = null, string $name = null)
    {
        $this->validateEmail($email);
        $this->{$var}[] = array($email,$name);
    }

    protected function setEmailSingle(string $var, string $email = null, string $name = null)
    {
        $this->validateEmail($email);
        $this->{$var} = array($email,$name);
    }

    /**
     * Validates an email
     *
     * @param string $email
     * @return void
     */
    protected function validateEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        throw new Exception(sprintf('Invalid email address %s', $email));
    }
    
    /**
     * Builds an array of headers for the email
     *
     * @return void
     */
    protected function buildHeaders()
    {
        $headers = [];

        $headers['MIME-Version'] = '1.0';
        $headers['Date'] = date('r');//RFC 2822
        $headers['Message-ID'] = $this->getMessageId();

        foreach ($this->additionalHeaders as $header => $value) {
            $headers[$header] = $value;
        }

        $headers['Subject'] = $this->getSubject();

        $optionals = ['sender'=>'Sender','replyTo'=>'Reply-To','returnPath'=>'Return-Path'];
        foreach ($optionals as $var => $header) {
            if ($this->{$var}) {
                $headers[$header] =  $this->formatAddress($this->{$var});
            }
        }

        $headers['From'] = $this->formatAddress($this->from);

        foreach (['to','cc','bcc'] as $var) {
            if ($this->{$var}) {
                $headers[ucfirst($var)] =  $this->formatAddresses($this->{$var});
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
    protected function buildMessage()
    {
        $message = [];

        $emailFormat = $this->format();
        $needsEncoding = $this->needsEncoding();
        $altBoundary = $boundary =  $this->getBoundary();
       

        if ($this->attachments and ($emailFormat ==='html' or $emailFormat ==='text')) {
            $message[] = '--'.$boundary;
            if ($emailFormat == 'text') {
                $message[] = 'Content-Type: text/plain; charset="' . $this->charset .'"';
            }
            if ($emailFormat == 'html') {
                $message[] = 'Content-Type: text/html; charset="' . $this->charset .'"';
            }
            if ($needsEncoding) {
                $message[] = 'Content-Transfer-Encoding: quoted-printable';
            }
            $message[] = '';
        }

        if ($this->attachments and $emailFormat == 'both') {
            $altBoundary = 'alt-' . $boundary;
            $message[] = '--' . $boundary;
            $message[] = 'Content-Type: multipart/alternative; boundary="' . $altBoundary .'"';
            $message[] = '';
        }

        if ($this->textMessage) {
            if ($emailFormat == 'both') {
                $message[] = '--' . $altBoundary;
                $message[] = 'Content-Type: text/plain; charset="' . $this->charset .'"';
                if ($needsEncoding) {
                    $message[] = 'Content-Transfer-Encoding: quoted-printable';
                }
                $message[] = '';
            }
            $message[] = $this->formatMessage($this->textMessage, $needsEncoding);
            $message[] = '';
        }

        if ($this->htmlMessage) {
            if ($emailFormat == 'both') {
                $message[] = '--' . $altBoundary;
                $message[] = 'Content-Type: text/html; charset="' . $this->charset .'"';
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
                $message[] = '--'. $boundary;
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
     * @return void
     */
    protected function formatMessage(string $message, bool $needsEncoding)
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
     * @return void
     */
    protected function getMessageId()
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
     * @return void
     */
    public function getDomain()
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
     * @return void
     */
    protected function getBoundary()
    {
        if ($this->boundary === null) {
            $this->boundary = md5(uniqid(microtime(true), true));
        }
        return $this->boundary;
    }

    /**
     * Gets and encodes the subject
     *
     * @return void
     */
    protected function getSubject()
    {
        if ($this->subject) {
            $this->subject = mb_encode_mimeheader($this->subject, $this->charset, 'B');
        }
        return $this->subject;
    }

    /**
     * Gets the content type for the email
     *
     * @return void
     */
    protected function getContentType()
    {
        if ($this->attachments) {
            return 'multipart/mixed; boundary="'.$this->getBoundary() .'"';
        }
        $emailFormat = $this->format();
        
        if ($emailFormat === 'both') {
            return 'multipart/alternative; boundary="'.$this->getBoundary() .'"';
        }

        if ($emailFormat === 'text') {
            return 'text/plain; charset="' . $this->charset .'"';
        }
        if ($emailFormat === 'html') {
            return 'text/html; charset="' . $this->charset .'"';
        }
    }

    /**
     * Gets/Sets the email format
     *
     * @return string|null $type
     */
    public function format($format = null)
    {
        if ($format === null) {
            return $this->emailFormat;
        }
        $this->emailFormat = $format;
        return $this;
    }
    /**
     * Checks if a message needs to be encoded
     *
     * @return void
     */
    protected function needsEncoding()
    {
        if (mb_check_encoding($this->subject, 'ASCII') === false) {
            return true;
        }
        $emailFormat = $this->format();
        if (($emailFormat  == 'text' or $emailFormat  =='both') and mb_check_encoding($this->textMessage, 'ASCII') === false) {
            return true;
        }
        if (($emailFormat  == 'html' or $emailFormat  =='both') and mb_check_encoding($this->htmlMessage, 'ASCII') === false) {
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
    protected function formatAddress(array $address)
    {
        list($email, $name) = $address;
        if ($name === null) {
            return $email;
        }
        $name = mb_encode_mimeheader($name, $this->charset, 'B');
        return "{$name} <{$email}>";
    }

    protected function formatAddresses(array $addresses)
    {
        $result = [];
        foreach ($addresses as $address) {
            $result[] = $this->formatAddress($address);
        }
        return implode(', ', $result);
    }
}
