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
namespace Origin\Mailbox;

use Origin\Core\Exception\Exception;
use Origin\Core\Exception\InvalidArgumentException;

/**
 * Lightweight MailParser
 */
class MailParser
{
   
    /**
     * Array of headers
     *
     * @var array
     */
    public $headers = [];

    /**
     * Parsed email parts
     *
     * @var array
     */
    private $parts = [];

    /**
     * @var resource
     */
    private $stream;

    /**
    * Mailparser resource
    *
    * @var resource
    */
    private $resource;

    /**
     * Standard DSNS (some servers might reply with variation)
     *
     * @var array
     */
    protected $dsns = [
        'Mail delivery failed',
        'Delivery Notification: Delivery has failed',
        'Returned mail',
        'Mail System Error - Returned Mail',
        'Undeliverable message',
        'Delivery Status Notification',
        'Nondeliverable mail',
        'Warning: could not send message',
        'Undeliverable Mail',
        'Undeliverable',
        'Undelivered Mail Returned to Sender',
        'Failure Notice',
        'Delivery Failure',
        'Message status - undeliverable',
        'Delivery Status Notification \(Failure\)'
    ];

    /**
     * English AutoReponder subjects
     */
    protected $autoResponses = [
        'Auto\:',
        'Automatic reply',
        'Auto-Reply|autoreply',
        'Auto Response',
        'Out of Office|Out of the office'
    ];

    private $messageLength = 0;

    /**
     * Destroy the MailParser when finished to clear up mem
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        # ubuntu: apt-get install php-mailparse
        if (! extension_loaded('mailparse')) {
            throw new Exception('Mailparse extension not loaded');
        }

        # Create resource
        $this->resource = mailparse_msg_create();
        mailparse_msg_parse($this->resource, $message);
        
        $this->messageLength = mb_strlen($message);

        # Get the structure
        $this->getStructure();

        if (empty($this->headers)) {
            throw new InvalidArgumentException('Content is not an email');
        }

        # store in temp stream
        $this->stream = fopen('php://temp', 'r+');
        fputs($this->stream, $message);
    }

    /**
     * Gets the email structure so that the parser can do its stuff
     *
     * @return void
     */
    private function getStructure(): void
    {
        $structure = mailparse_msg_get_structure($this->resource);

        foreach ($structure as $id) {
            $part = mailparse_msg_get_part($this->resource, $id);
            $this->parts[$id] = mailparse_msg_get_part_data($part);
        }
        $this->headers = $this->parts[1]['headers'];
    }

    /**
     * Returns the message header (string)
     *
     * @return string
     */
    public function header(): string
    {
        return trim($this->extract(0, $this->parts[1]['starting-pos-body'], "\r\n"));
    }

    /**
    * Gets the body of the message (this includes all parts etc)
    *
    * @return string
    */
    public function body(): string
    {
        return $this->extract($this->parts[1]['starting-pos-body'], $this->messageLength);
    }

    /**
     * Returns the full message (header and body)
     *
     * @return string
     */
    public function message(): string
    {
        rewind($this->stream);

        return stream_get_contents($this->stream);
    }

    /**
     * Gets the header or array or specific header
     *
     * @param string $header
     * @return string|array|null
     */
    public function headers(string $header = null, bool $decode = true)
    {
        $out = null;
        if ($header === null) {
            $out = $this->parts[1]['headers'];
        } else {
            $out = $this->parts[1]['headers'][$header] ?? null;
        }

        return $out ? $this->decodeHeader($out) : null;
    }

    /**
     * Gets the decoded message, if its a text message, it returns the text version, html returns html and
     * if its multipart, then it returns the highest priority version as defined by RFC
     *
     * @return string|null
     */
    public function decoded(): ?string
    {
        $type = $this->detectContentType();

        return $this->extractPart($type);
    }

    /**
     * Gets the text part of the message
     *
     * @return string|null
     */
    public function textPart(): ?string
    {
        return $this->extractPart('text');
    }

    /**
     * Gets the HTML part of the message
     *
     * @return string|null
     */
    public function htmlPart(): ?string
    {
        return $this->extractPart('html');
    }

    /**
     * Gets the message body
     *
     * @param  string $type You can use any of the following types:
     *  - html : this will return HTML version
     *  - text : wil return text version
     * @return string|null body
     */
    private function extractPart(string $type): ?string
    {
        $mime = ($type === 'html') ? 'text/html' : 'text/plain';

        $body = null;
        foreach ($this->parts as $part) {
            if (isset($part['content-disposition']) && $part['content-disposition'] === 'attachment') {
                continue;
            }
            if (! isset($part['content-type']) || $part['content-type'] !== $mime) {
                continue;
            }

            $encoding = $part['headers']['content-transfer-encoding'] ?? '';

            if (is_array($encoding)) {
                $encoding = $encoding[0];
            }

            $body = $this->extract($part['starting-pos-body'], $part['ending-pos-body']);
            if ($this->needsDecoding($encoding)) {
                $body = $this->decodeContent($body, $encoding);
            }

            break;
        }

        return $body;
    }

    /**
     * Checks if the email message has any attachments
     *
     * @return boolean
     */
    public function hasAttachments(): bool
    {
        foreach ($this->parts as $data) {
            if (isset($data['content-disposition']) && strtolower($data['content-disposition']) === 'attachment') {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a list of attachments each attachment has the keys name,type,size and tmp
     *
     * @return array
     */
    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->parts as $data) {
            if (isset($data['content-disposition']) && strtolower($data['content-disposition']) === 'attachment') {
                $tmp = tempnam(sys_get_temp_dir(), 'O');
                $fh = fopen($tmp, 'w');
                fwrite($fh, $this->extractAttachment(
                    $data['starting-pos-body'],
                    $data['ending-pos-body'],
                    $data['transfer-encoding']
                ) ?: ''); // allow empty strings for invalid attachments @see 550-address-not-found.eml
                fclose($fh);

                $attachments[] = [
                    'name' => $data['disposition-filename'],
                    'type' => mime_content_type($tmp),
                    'size' => filesize($tmp),
                    'tmp' => $tmp
                ];
            }
        }

        return $attachments;
    }

    /**
     * Extracts and decodes an attachment if needed
     *
     * @param integer $start
     * @param integer $end
     * @param string $encoding
     * @return string|null
     */
    private function extractAttachment(int $start, int $end, string $encoding): ?string
    {
        $content = $this->extract($start, $end);

        return ($content && $this->needsDecoding($encoding)) ? $this->decodeContent($content, $encoding) : $content;
    }

    /**
     * Parse RFC 822 compliant addresses list from the to, from, cc or bcc headers. Do not include
     * the header part. e.g. 'To: '
     *
     * @param string $list 'somebody@originphp.com, Jamiel Sharief <jamiel@originphp.com>'
     * @return array
     */
    public function parseAddresses(string $list): ?array
    {
        $out = [];
        $addresses = mailparse_rfc822_parse_addresses($list);
        if (is_array($addresses)) {
            foreach ($addresses as $address) {
                $out[] = [
                    'name' => $address['display'] === $address['address'] ? null : $address['display'],
                    'email' => $address['address']
                ];
            }
        }

        return $out;
    }

    /**
     * Checks if encoding type requires decoding
     *
     * @param string $encoding base64,8bit,7bit, quoted-printable
     * @return boolean
     */
    private function needsDecoding(string $encoding): bool
    {
        return in_array(strtolower($encoding), ['base64', 'quoted-printable','8bit','binary']);
    }

    /**
     * Decodes content
     *
     * @internal
     * @param string $content
     * @param string $encoding
     * @return string
     */
    private function decodeContent(string $content, string $encoding): string
    {
        $encoding = strtolower($encoding);

        if ($encoding === 'base64') {
            return base64_decode($content);
        }

        if ($encoding === 'quoted-printable') {
            return quoted_printable_decode($content);
        }

        # not tested yet
        if ($encoding === '8bit') {
            return quoted_printable_decode(imap_8bit($content));
        }

        # never seen an email with binary
        if ($encoding === 'binary') {
            return base64_decode(imap_binary($content)); // is this correct?
        }

        throw new InvalidArgumentException('Invalid encoding ' . $encoding);
    }

    /**
     * Decodes a header array or indivdual header
     *
     * @param string|array $header
     * @return string|array
     */
    private function decodeHeader($header)
    {
        if (is_array($header)) {
            foreach ($header as $key => $value) {
                $header[$key] = $this->decodeHeader($value);
            }

            return $header;
        }
  
        return iconv_mime_decode($header, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
    }

    /**
     * Extracts a part of an email.
     *
     * @internal in the GMAIL DSN there is an attachment which has no data (and two return-path headers) so
     * I have added a quick check to ensure start/end positions are valid
     *
     * @param integer $start
     * @param integer $end
     * @return string|null
     */
    private function extract(int $start, int $end): ?string
    {
        $out = null;

        if ($end > $start) {
            fseek($this->stream, $start);
            $out = fread($this->stream, $end - $start);
            rewind($this->stream);
        }
       
        return $out;
    }

    /**
     * Checks if a message is a delivery status report
     *
     * @return bool
     */
    public function deliveryStatusReport(): bool
    {
        return $this->parts[1]['content-type'] === 'multipart/report';
    }

    /**
     * Checks if a message has multiple parts
     *
     * @return boolean
     */
    public function multipart(): bool
    {
        $contentType = $this->parts[1]['content-type'];

        return in_array($contentType, ['multipart/related', 'multipart/mixed','multipart/alternative']);
    }

    /**
     * Gets the Content type of the message
     *
     * @return string
     */
    public function contentType(): string
    {
        return $this->parts[1]['content-type']; //  ?? 'text/plain';
    }

    /**
     * Checks if the message looks like its a bounced, impossible to find 100% since there is no common format. This
     * should catch 90%+ of bounces.
     *
     * status codes beginning with 5 are permenent and 4 are temporary
     *
     * @link https://www.ietf.org/rfc/rfc3463.txt
     *
     * @return boolean
     */
    public function bounced(): bool
    {
        if (! $this->deliveryStatusReport()) {
            return false;
        }

        /**
         * Important: Use regex since some headers can multiple values
         */
        $header = $this->header();

        /**
         * Check for the x-failed-recipients header
         */
        if (preg_match('/^x-failed-recipients:/im', $header)) {
            return true;
        }

        /**
         * Check the message either has empty return path or from mailer-daemon or postmaster
         */
        if (! preg_match('/^return-path: ?< ?>/im', $header) && ! preg_match('/^from:.*(mailer-daemon|postmaster)/im', $header)) {
            return false;
        }
       
        /**
         * Check subject for standard delivery status notification messages
         */
        if (preg_match('/^subject:.*(' . implode('|', $this->dsns) .')/im', $header)) {
            return true;
        }

        $body = $this->body();

        /**
         * Check for this which can be found in the delivery status message itself
         */
        if (preg_match('/^action: failed/im', $body)) {
            return true;
        }
       
        /**
         * Check for a mail server error e.g 500 1.1.1 in the body
         */
        return (bool) preg_match('/5[\d\d]\s(\d\.\d\.\d)\s/', $body);
    }

    /**
     * Checks if a message is an autoresponder. There are other methods
     * as well but they are not official and unpredictable.
     *
     * @return boolean
     */
    public function autoResponder(): bool
    {
        if ($this->deliveryStatusReport()) {
            return false;
        }

        $header = $this->header();

        /**
        * Check auto-submitted header defined in RFC 3834.
        * Definition:
        * Automatic responses SHOULD NOT be issued in response to any
        * message which contains an Auto-Submitted header field (see below),
        * where that field has any value other than "no".
        *
        * @link http://tools.ietf.org/html/rfc3834
        */
        if (preg_match('/^auto-submitted:.*([^(no)])/im', $header)) {
            return true;
        }
       
        /**
         * Check X-Auto-Response-Suppress header. This is defined by Microsoft
         * and used by products such as Outlook, Exchange etc.
         *
         * @link https://msdn.microsoft.com/en-us/library/ee219609(v=EXCHG.80).aspx
         */
        if (preg_match('/^x-auto-response-suppress:.*(DR|ALL|AUTO)/im', $header)) {
            return true;
        }
       
        /**
         * This is commonly used but is discouraged.
         * @link http://www.faqs.org/rfcs/rfc2076.html
         */
        if (preg_match('/^precedence:.*(auto-reply)/im', $header)) {
            return true;
        }

        /**
         * Check subject for standard auto responder subject text
         *
         * Office 365 autoresponders and unkown providers by well established bank not setting headers, so the
         * best the thing to do here is to check the subject.
         */
        return (bool) preg_match('/^subject: (' . implode('|', $this->autoResponses) .')/im', $header);
    }

    /**
     * Detects the content type to be used by the body
     *
     * @return string
     */
    public function detectContentType(): string
    {
        $contentType = $this->contentType();

        if ($contentType === 'text/plain') {
            return 'text';
        }
        if ($contentType === 'text/html') {
            return 'html';
        }

        /**
         * The order of the parts are significiant, later is more important.
         * RFC1341 states: In general, user agents that compose multipart/alternative entities should place the
         * body parts in increasing order of preference, that is, with the preferred format last.
         *
         * @link https://www.w3.org/Protocols/rfc1341/7_2_Multipart.html
         */
        if ($contentType === 'multipart/alternative') {
            $last = end($this->parts);

            return $last['content-type'] === 'text/html' ? 'html' : 'text';
        }

        $out = 'text';

        /**
         * Two versions of the same mesage are provided but check html version actually exists as
         * it could be richtext.
         * @link http://www.freesoft.org/CIE/RFC/1521/18.htm
         */
        if (in_array($contentType, ['multipart/related', 'multipart/mixed'])) {
            foreach ($this->parts as $part) {
                if ($part['content-type'] === 'text/html') {
                    $out = 'html';
                    break;
                }
            }
        } elseif ($contentType === 'multipart/report') {
            // Parse email reports, original message is included so this can be tricky.
            foreach ($this->parts as $part) {
                if ($part['content-type'] === 'message/delivery-status') {
                    $out = 'text';
                    break;
                }
                if ($part['content-type'] === 'text/html') {
                    $out = 'html';
                    break;
                }
            }
        }

        return $out;
    }

    public function __destruct()
    {
        $this->parts = $this->headers = [];
        fclose($this->stream);
        mailparse_msg_free($this->resource);
    }

    /**
     * Converts this mail into a full message (string)
     */
    public function __toString()
    {
        return $this->message();
    }
}
