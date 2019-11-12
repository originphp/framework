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

use Origin\Core\Exception\Exception;
use Origin\Core\Exception\InvalidArgumentException;

/**
 * Lightweight MailParser
 */
class MailParser
{
    /**
     * Mailparser resource
     *
     * @var resource
     */
    private $resource;

    /**
     * Array of headers
     *
     * @var array
     */
    private $headers = [];

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
     * Returns the raw header
     *
     * @return string
     */
    public function rawHeader(): string
    {
        return $this->extract(0, $this->parts[1]['starting-pos-body']);
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
     * Gets the message body
     *
     * @param  string $type text,html,raw
     * @return string body
     */
    public function body(string $type = null): string
    {
        if ($type === null) {
            $type = $this->detectContentType();
        }

        if (! in_array($type, ['text', 'html', 'raw'])) {
            throw new InvalidArgumentException('Invalid type. text, html or raw only');
        }

        if ($type === 'raw') {
            return $this->extract($this->parts[1]['starting-pos-body'], $this->parts[1]['ending-pos-body']);
        }

        $mime = ($type === 'html') ? 'text/html' : 'text/plain';

        $body = null;
        foreach ($this->parts as $part) {
            if (isset($part['content-disposition']) and $part['content-disposition'] === 'attachment') {
                continue;
            }
            if (! isset($part['content-type']) or $part['content-type'] !== $mime) {
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
        foreach ($this->parts as $key => $data) {
            if (isset($data['content-disposition']) and strtolower($data['content-disposition']) == 'attachment') {
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
        foreach ($this->parts as $key => $data) {
            if (isset($data['content-disposition']) and strtolower($data['content-disposition']) == 'attachment') {
                $tmp = tempnam(sys_get_temp_dir(), 'O');

                $fh = fopen($tmp, 'w');
                fwrite($fh, $this->extract($data['starting-pos-body'], $data['ending-pos-body']));
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
        return in_array(strtolower($encoding), ['base64', 'quoted-printable']);
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
        if ($encoding == 'base64') {
            return base64_decode($content);
        }

        if ($encoding == 'quoted-printable') {
            return quoted_printable_decode($content);
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
     * Extracts a part of an email
     *
     * @param integer $start
     * @param integer $end
     * @return string
     */
    private function extract(int $start, int $end): string
    {
        fseek($this->stream, $start);
        $out = fread($this->stream, $end - $start);
        rewind($this->stream);

        return $out;
    }

    /**
     * Detects the body content type
     *
     * @return string
     */
    private function detectContentType(): string
    {
        $contentType = $this->parts[1]['content-type'] ?? 'text/plain';

        if ($contentType === 'text/plain') {
            return 'text';
        }
        if ($contentType === 'text/html') {
            return 'html';
        }

        /**
         * Two versions of the same mesage are provided but check html version actually exists as
         * it could be richtext.
         * @link http://www.freesoft.org/CIE/RFC/1521/18.htm
         */
        if (in_array($contentType, ['multipart/related', 'multipart/alternative', 'multipart/mixed'])) {
            $out = 'text';
            foreach ($this->parts as $part) {
                if ($part['content-type'] == 'text/html') {
                    $out = 'html';
                    break;
                }
            }

            return $out;
        }

        /**
         * Parse email reports, original message is included so this can be tricky.
         */
        if ($contentType === 'multipart/report') {
            $out = 'text';
            foreach ($this->parts as $part) {
                if ($part['content-type'] == 'message/delivery-status') {
                    $out = 'text';
                    break;
                }
                if ($part['content-type'] == 'text/html') {
                    $out = 'html';
                    break;
                }
            }

            return $out;
        }
    }

    public function __destruct()
    {
        $this->parts = $this->headers = [];
        fclose($this->stream);
        mailparse_msg_free($this->resource);
    }
}
