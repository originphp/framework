<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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

class Email
{
    protected $to = [];

    protected $from = [];

    protected $cc = [];

    protected $bcc = [];

    protected $replyTo = [];

    protected $sender = [];

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

    public function __construct()
    {
        if (extension_loaded('mbstring') === false) {
            throw new Exception('mbstring extension is not loaded');
        }
        mb_internal_encoding($this->charset);
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
        $this->setEmail('from', $email, $name);
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
        $this->setEmail('sender', $email, $name);
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
        $this->setEmail('replyTo', $email, $name);
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
        $this->setEmail('returnPath', $email, $name);
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

    public function send()
    {
        // Check message is set and to and from etc
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

    protected function validateEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        throw new Exception('Invalid email address ' . $email);
    }

    protected function buildMessageHeader()
    {
        $this->setHeader('MIME-Version', '1.0');
        $this->setHeader('Date', date('r'));//RFC 2822
        if ($this->bcc) {
            $this->setHeader('Bcc', $this->formatAddresses($this->bcc));
        }
        $this->setHeader('Message-ID', $this->getMessageId());

        foreach ($this->additionalHeaders as $header => $value) {
            $this->setHeader($header, $value);
        }

        $this->setHeader('Subject', $this->getSubject());
        $this->setHeader('From', $this->formatAddresses($this->from));
        $this->setHeader('To', $this->formatAddresses($this->to));

        if ($this->cc) {
            $this->setHeader('Cc', $this->formatAddresses($this->cc));
        }
        
        $this->setHeader('Content-Type', $this->getContentType());
        if ($this->needsEncoding() and empty($this->attachments) and $this->emailFormat() !== 'both') {
            $this->setHeader('Content-Transfer-Encoding', 'quoted-printable');
        }
    }

    protected function createBody()
    {
        $message = [];

        $emailFormat = $this->emailFormat();
        $needsEncoding = $this->needsEncoding();
        $altBoundary = $boundary =  $this->getBoundary();
       
        if ($emailFormat == 'both' and $this->attachments) {
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
            $message[] = $this->prepareMessage($this->textMessage, $needsEncoding);
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
            $message[] = $this->prepareMessage($this->htmlMessage, $needsEncoding);
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
        //pr($message);
        return rtrim(implode("\r\n", $message));
    }

    /**
     * Standardizes the line endings and encodes if needed
     *
     * @param string $message
     * @return void
     */
    protected function prepareMessage(string $message, bool $needsEncoding)
    {
        $message = preg_replace("/\r\n|\n/", "\r\n", $message);
        if ($needsEncoding) {
            $message = quoted_printable_encode($message);
        }
        return $message;
    }


    

    protected function setHeader(string $name, string $value)
    {
        $this->headers[$name] = $value;
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

    public function getDomain()
    {
        $domain = php_uname('n');
        if ($this->from) {
            $email = $this->from[0][0];
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
        $emailFormat = $this->emailFormat();
        
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
     * Gets the email format
     *
     * @return string|null $type
     */
    protected function emailFormat()
    {
        if ($this->textMessage and $this->htmlMessage === null) {
            return 'text';
        }
        
        if ($this->htmlMessage and $this->textMessage === null) {
            return 'html';
        }
        if ($this->htmlMessage and $this->textMessage) {
            return 'both';
        }
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
        $emailFormat = $this->emailFormat();
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
