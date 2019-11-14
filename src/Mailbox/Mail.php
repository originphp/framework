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

class Mail
{
    /**
     * Message ID
     *
     * @var string
     */
    public $messageId;

    /**
     * @var string|array
     */
    public $to;

    /**
     * @var string|array
     */
    public $cc;

    /**
     * @var string|array
     */
    public $bcc;

    /**
     * From email address
     *
     * @var string
     */
    public $from;

    /**
     * @var string
     */
    public $replyTo;

    /**
     * @var string
     */
    public $sender;

    /**
     * @var string
     */
    public $returnPath;

    /**
     * Email subject
     *
     * @var string
     */
    public $subject;

    /**
     * Email header
     *
     * @var string
     */
    public $header;

    /**
     * Email body
     *
     * @var string
     */
    public $body;

    /**
     * @var string|null
     */
    public $htmlPart;

    /**
     * @var string|null
     */
    public $textPart;

    /**
     * Decoded message body, if its multipart alternative
     * it will contain the highest priority one
     *
     * @var string
     */
    public $decoded;

    /**
     * Array of attachments
     *
     * @var array
     */
    public $attachments = [];

    /**
     * Email headers in array format
     *
     * @var array
     */
    private $headers;

    /**
     * @var string
     */
    private $contentType;

    /**
     * @var boolean
     */
    private $bounced = false;

    /**
     * @var boolean
     */
    private $autoResponder = false;

    /**
     * @var boolean
     */
    private $multipart = false;

    /**
     * @var boolean
     */
    private $deliveryStatusReport = false;

    /**
     * An array of all the recipients
     *
     * @var array
     */
    private $recipients = [];

    public function __construct(string $message = null)
    {
        if ($message) {
            $this->parse($message);
        }
    }

    private function parse(string $message)
    {
        $parser = new MailParser($message);
        $this->messageId = $parser->headers('message-id');
        $this->to = $this->parseAddresses($parser->headers('to'));
        $this->cc = $this->parseAddresses($parser->headers('cc'));
        $this->bcc = $this->parseAddresses($parser->headers('bcc'));
        $this->subject = $parser->headers('subject');
        $this->replyTo = $this->parseSingleAddress($parser->headers('reply-to'));
        $this->from = $this->parseSingleAddress($parser->headers('from'));
        $this->sender = $this->parseSingleAddress($parser->headers('sender'));
        $this->returnPath = $this->parseSingleAddress($parser->headers('return-path'));
        $this->header = $parser->header();
        $this->headers = $parser->headers();
        $this->body = $parser->body();
        $this->htmlPart = $parser->htmlPart();
        $this->textPart = $parser->textPart();
        $this->decoded = $parser->decoded();
        $this->contentType = $parser->contentType();
        $this->bounced = $parser->bounced();
        $this->autoResponder = $parser->autoResponder();
        $this->deliveryStatusReport = $parser->deliveryStatusReport();
        $this->attachments = $parser->attachments();
        $this->recipients = (array) $this->to + (array) $this->cc + (array) $this->bcc;
    }

    /**
     * Extracts a single email address from a header line
     *
     * @param string $header
     * @return string|null
     */
    private function parseSingleAddress(string $header = null) : ?string
    {
        if ($header === null) {
            return null;
        }
        $addresses = mailparse_rfc822_parse_addresses($header);

        return $addresses[0]['address'];
    }

    /**
     * Extracts multiple email addresss from a header line
     *
     * @param string $header
     * @return string|null
     */
    private function parseAddresses(string $header = null)
    {
        if ($header === null) {
            return null;
        }
        $out = [];
        $addresses = mailparse_rfc822_parse_addresses($header);
        foreach ($addresses as $address) {
            $out[] = $address['address'];
        }

        return count($out) === 1 ? $out[0] : $out;
    }

    /**
     * Returns an array of emails addresses using to, cc and bcc
     *
     * @return array
     */
    public function recipients(): array
    {
        return $this->recipients;
    }

    /**
     * Gets an individual header
     *
     * @param string $key
     * @return mixed
     */
    public function headers(string $key = null)
    {
        if ($key === null) {
            return $this->headers;
        }

        return $this->headers[$key] ?? null;
    }

    /**
     * Gets the content type of this message
     *
     * @return string
     */
    public function contentType(): string
    {
        return $this->contentType;
    }

    /**
     * If this message is bounce
     *
     * @return boolean
     */
    public function isBounce(): bool
    {
        return $this->bounced;
    }
    /**
     * If this message is an autoresponder
     *
     * @return boolean
     */
    public function isAutoResponder(): bool
    {
        return $this->autoResponder;
    }
    /**
     * If this email is a Delivery Status Report (DSR)
     *
     * @return boolean
     */
    public function isDeliveryStatusReport(): bool
    {
        return $this->deliveryStatusReport;
    }
    /**
     * If this email has multiple parts
     *
     * @return boolean
     */
    public function isMultipart(): bool
    {
        return $this->multipart;
    }
    /**
     * If this email has attachments
     *
     * @return boolean
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }

    /**
     * If this email has a HTML part
     *
     * @return boolean
     */
    public function hasHtml() : bool
    {
        return ! empty($this->htmlPart);
    }

    /**
     * If this email has a Text part
     *
     * @return boolean
     */
    public function hasText() : bool
    {
        return ! empty($this->textPart);
    }

    /**
     * Returns this full email message (header + body)
     *
     * @return string
     */
    public function message() : string
    {
        return $this->header . "\r\n\r\n" . $this->body;
    }

    public function __toString()
    {
        return $this->message();
    }
}
