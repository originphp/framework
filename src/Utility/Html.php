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

namespace Origin\Utility;

use DOMNode;
use DOMXPath;
use DOMDocument;

class Html
{
    /**
     * Converts text to html
     *
     * @param string $text
     * @param array $options (tag)
     *   -tag: default:p tag to wrap lines ines e.g. ['tag'=>'div']
     *   -escape: default is true. Escapes text before converting it to html.
     * @return string
     */
    public static function fromText(string $text, array $options = []): string
    {
        $options += ['tag' => 'p','escape' => true];
        if ($options['escape']) {
            $text = static::escape($text);
        }
        $out = [];
        $text = str_replace("\r\n", "\n", $text); // Standarize line endings
        $lines = explode("\n\n", $text);
        foreach ($lines as $line) {
            $line = str_replace("\n", '<br>', $line);
            $out[] = sprintf('<%s>%s</%s>', $options['tag'], $line, $options['tag']);
        }

        return implode("\n", $out);
    }

    /**
     * Minifies HTML
     *
     * @param string $html
     * @return string|null
     */
    public static function minify(string $html): ?string
    {
        $keepWhitespace = ['address', 'pre', 'script', 'style'];
        $keepWhitespaceAround = [
            'a', 'abbr', 'acronym', 'b', 'br', 'button', 'cite', 'code', 'del', 'em', 'i', 'img', 'input', 's', 'select', 'small', 'span', 'strong', 'textarea', 'u',
        ];

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        $doc->normalizeDocument();

        $html = preg_replace('/(?=<!--)([\s\S]*?)-->/', '', $html); // Remove comments

        @$doc->loadHTML($html, LIBXML_HTML_NODEFDTD);
        $x = new DOMXPath($doc);
        $nodes = $x->query('//text()');

        foreach ($nodes as $node) {
            // Check parent, plus 1 parent level e.g pre/code
            if (in_array($node->parentNode->nodeName, $keepWhitespace) or in_array($node->parentNode->parentNode->nodeName, $keepWhitespace)) {
                continue;
            }
            // Will using regex cause performance issues on large html files?
            $node->nodeValue = str_replace(["\r\n", "\n", "\r", "\t"], '', $node->nodeValue);
            $node->nodeValue = preg_replace('/\s{2,}/', ' ', $node->nodeValue);

            # Check parent and one level up for e.g pre + code Not sure of other examples
            if (! in_array($node->parentNode->nodeName, $keepWhitespaceAround)) {
                if ($node->previousSibling and ! in_array($node->previousSibling->nodeName, $keepWhitespaceAround)) {
                    $node->nodeValue = ltrim($node->nodeValue);
                }
                if ($node->nextSibling and ! in_array($node->nextSibling->nodeName, $keepWhitespaceAround)) {
                    $node->nodeValue = rtrim($node->nodeValue);
                }
            }
        }

        return trim($doc->saveHTML() ?? 'An error occured');
    }

    /**
     * A Simple Html To Text Function.
     *
     * @param string $html
     * @param array $options The options keys are
     *  - format: default:true formats output. If false then it will provide a cleaner
     * @return string
     */
    public static function toText(string $html, array $options = []): string
    {
        $options += ['format' => true];
        $html = static::stripTags($html, ['script', 'style', 'iframe']);
        $html = static::minify($html);

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        $html = str_replace(["\r\n", "\n"], PHP_EOL, $html); // Standardize line endings

        /**
         * Create a text version without formatting, just adds new lines, indents for lists, and list type, e.g number
         * or *
         */
        if ($options['format'] === false) {
            // ul/li needs to be formatted to work with sublists
            $html = preg_replace('/^ +/m', '', $html); // remove whitespaces from start of each line
            $html = preg_replace('/(<\/(h1|h2|h3|h4|h5|h6|tr|blockquote|dt|dd|table|p)>)/', '$1' . PHP_EOL, $html);
            $html = preg_replace('/(<(h1|h2|h3|h4|h5|h6|table|blockquote|p[^re])[^>]*>)/', PHP_EOL . '$1', $html);
            $html = str_replace("</tr>\n</table>", '</tr></table>', $html);
            $html = preg_replace('/(<br>)/', '$1' . PHP_EOL, $html);
            $html = preg_replace('/(<\/(th|td)>)/', '$1 ', $html); //Add space
        }

        @$doc->loadHTML($html, LIBXML_HTML_NODEFDTD);
        $process = ['a', 'img', 'br', 'code', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table','li','ul', 'ol', 'blockquote'];

        if ($options['format'] === false) {
            $process = ['ul', 'ol'];
        }
    
        /**
         * Do not sort. The order is important. Certain elements need to be adjusted first including links, images
         */
        foreach ($process as $needle) {
            $nodes = $doc->getElementsByTagName($needle);
            foreach ($nodes as $node) {
                static::processTag($node, $doc);
            }
        }
 
        return trim($doc->textContent);
    }

    /**
     * Check if value needs converting and convert
     *
     * @param string $value
     * @return mixed
     */
    protected static function htmlspecialchars(string $value)
    {
        if (strpos($value, '&') !== false) {
            $value = htmlspecialchars($value);
        }

        return $value;
    }
    /**
     * Processes a tag from a DOMDocument
     *
     * @internal Attempting to modify the dom causes strange issues and even recursion
     * @param Node $tag
     * @param DomDocument $doc
     * @return void
     */
    protected static function processTag(DomNode $tag, DomDocument $doc): void
    {
        $value = static::htmlspecialchars($tag->nodeValue);

        switch ($tag->tagName) {
            case 'a':
                $tag->nodeValue = "[{$value}](" . static::htmlspecialchars($tag->getAttribute('href'))  . ')';
                break;
            case 'br':
                $tag->nodeValue = PHP_EOL;
                break;
            case 'code':
                // indent multi line
                if (strpos($tag->nodeValue, PHP_EOL) !== false) {
                    $tag->nodeValue = PHP_EOL . '   ' . str_replace(PHP_EOL, PHP_EOL . '   ', $value) . PHP_EOL;
                }
                break;
            case 'blockquote':
                $tag->nodeValue = PHP_EOL . '"' . $value . '"' . PHP_EOL;
                break;
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':

                $repeat = '=';
                if ($tag->tagName !== 'h1') {
                    $repeat = '-';
                }
                /**
                 * Use insertBefore instead of replace which causes issues even if you
                 * use array to loop
                 */
                $u = str_repeat($repeat, mb_strlen($tag->nodeValue));
                $div = $doc->createElement('div', "\n{$value}\n{$u}\n");
                $tag->parentNode->insertBefore($div, $tag);
                $tag->nodeValue = null;

                break;
                case 'li':
                    if ($tag->hasChildNodes()) {
                        foreach ($tag->childNodes as $child) {
                            if (in_array($child->nodeName, ['ul','ol'])) {
                                static::processTag($child, $doc);
                            }
                        }
                    }
                break;

            case 'img':
   
                $alt = '';
                if ($tag->hasAttribute('alt')) {
                    $alt = $tag->getAttribute('alt') . ' ';
                }
                $alt = htmlspecialchars($alt);
                $tag->nodeValue = "[image: {$alt}" . static::htmlspecialchars($tag->getAttribute('src')) . ']';
                break;
            case 'ol':
                $count = 1;
                $lineBreak = PHP_EOL;
                $indent = static::getIndentLevel($tag);
                $pre = str_repeat(' ', $indent);
                foreach ($tag->childNodes as $child) {
                    if (isset($child->tagName) and $child->tagName === 'li') {
                        $child->nodeValue = $lineBreak . $pre .  $count . '. ' . static::htmlspecialchars($child->nodeValue);
                        $child->nodeValue = rtrim($child->nodeValue) . PHP_EOL; // friendly with nested lists
                        $count++;
                        $lineBreak = null;
                    }
                }
                break;
            case 'p':
                $tag->nodeValue = PHP_EOL . $value . PHP_EOL;
                break;

            case 'table':
                $data = [];
                $headers = false;
                foreach ($tag->getElementsByTagName('tr') as $node) {
                    $row = [];
                    foreach ($node->childNodes as $child) {
                        if (isset($child->tagName) and ($child->tagName === 'td' or $child->tagName === 'th')) {
                            if ($child->tagName === 'th') {
                                $headers = true;
                            }
                            $row[] = $child->nodeValue;
                        }
                    }
                    $data[] = $row;
                }
                if ($data) {
                    $data = static::arrayToTable($data, $headers);
                }
                
                // Replacing can cause issues
                $div = $doc->createElement('div', PHP_EOL . implode(PHP_EOL, $data) . PHP_EOL);
                $tag->parentNode->insertBefore($div, $tag);
                $tag->nodeValue = null;

                break;
            case 'ul':
         
                $lineBreak = PHP_EOL;
                $indent = static::getIndentLevel($tag);
                $pre = str_repeat(' ', $indent);

                foreach ($tag->childNodes as $child) {
                    if (isset($child->tagName) and $child->tagName === 'li') {
                        $child->nodeValue = $lineBreak . $pre . '* ' .   static::htmlspecialchars($child->nodeValue);
                        $child->nodeValue = rtrim($child->nodeValue) . PHP_EOL; // friendly with nested lists
                        $lineBreak = null;
                    }
                }
                break;
        }
        // Remove all attributes
        foreach ($tag->attributes as $attr) {
            $tag->removeAttribute($attr->nodeName);
        }
    }

    /**
     * Gets the indent level for ul/ol
     *
     * @param DOMNode $node
     * @return integer
     */
    protected static function getIndentLevel(DOMNode $node): int
    {
        $indent = 0;
        $checkLevelUp = true;
        $current = $node;
    
        while ($checkLevelUp) {
            if ($current->parentNode->nodeName === 'li') {
                $current = $current->parentNode;
                $indent = $indent + 3;
            } else {
                $checkLevelUp = false;
            }
        }

        return $indent;
    }

    /**
     * Internal for creating table
     *
     * @param array $array
     * @param boolean $headers
     * @return string
     */
    protected static function arrayToTable(array $array, bool $headers = true): array
    {
        // Calculate width of each column
        $widths = [];
        foreach ($array as $row) {
            foreach ($row as $columnIndex => $cell) {
                if (! isset($widths[$columnIndex])) {
                    $widths[$columnIndex] = 0;
                }
                $width = strlen($cell) + 4;
                if ($width > $widths[$columnIndex]) {
                    $widths[$columnIndex] = $width;
                }
            }
        }

        $out = [];
        $seperator = '';

        foreach ($array[0] as $i => $cell) {
            $seperator .= str_pad('+', $widths[$i], '-', STR_PAD_RIGHT);
        }
        $seperator .= '+';
        $out[] = $seperator;

        if ($headers) {
            $headers = '|';
            foreach ($array[0] as $i => $cell) {
                $headers .= ' ' . str_pad($cell, $widths[$i] - 2, ' ', STR_PAD_RIGHT) . '|';
            }
            $out[] = $headers;
            $out[] = $seperator;
            array_shift($array);
        }

        foreach ($array as $row) {
            $cells = '|';
            foreach ($row as $i => $cell) {
                $cells .= ' ' . str_pad($cell, $widths[$i] - 2, ' ', STR_PAD_RIGHT) . '|';
            }
            $out[] = $cells;
        }
        $out[] = $seperator;

        return $out;
    }

    /**
     * Cleans up user inputted html for saving to a database
     *
     * @param string $html
     * @param array tags An array of tags to be allowed e.g. ['p','h1'] or to
     * only allow certain attributes on tags ['p'=>['class','style]];
     * The defaults are :
     * ['h1', 'h2', 'h3', 'h4', 'h5', 'h6','p','i', 'em', 'strong', 'b', 'del', 'blockquote' => ['cite']
     * 'a','ul', 'li', 'ol', 'br','code', 'pre', 'div', 'span']
     * @return string
     */
    public static function sanitize(string $html, array $tags = null): string
    {
        $defaults = [
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6','p','i', 'em', 'strong', 'b', 'del', 'blockquote' => ['cite'],'a','ul', 'li', 'ol', 'br','code', 'pre', 'div', 'span',
        ];

        if ($tags === null) {
            $tags = $defaults;
        }

        // Normalize tag options
        $options = [];
        foreach ($tags as $key => $value) {
            if (is_int($key)) {
                $key = $value;
                $value = [];
            }
            $options[$key] = $value;
        }

        $html = str_replace(["\r\n", "\n"], PHP_EOL, $html); // Standardize line endings
        /**
         * When document is imported it will have HTML and body tag.
         */
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;

        /**
         * Add html/body but not doctype. body will be removed later
         */
        @$doc->loadHTML($html, LIBXML_HTML_NODEFDTD);
        foreach ($doc->firstChild->childNodes as $node) {
            static::_sanitize($node, $options); // body
        }
        
        return preg_replace('~<(?:/?(?:html|body))[^>]*>\s*~i', '', $doc->saveHTML());
    }

    /**
     * Workhorse
     *
     * @param DomNode $node
     * @param array $options
     * @return void
     */
    protected static function _sanitize(DomNode $node, array $tags = []): void
    {
        if ($node->hasChildNodes()) {
            for ($i = 0; $i < $node->childNodes->length; $i++) {
                static::_sanitize($node->childNodes->item($i), $tags);
            }
        }
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        $remove = $change = $attributes = [];
        if (! isset($tags[$node->nodeName]) and $node->nodeName !== 'body') {
            $remove[] = $node;
            /* This is for keeping text between divs. Keep for now until committed
            foreach ($node->childNodes as $child) {
                     $change[] = [$child, $node];
                 }
             */
        }

        if ($node->attributes) {
            foreach ($node->attributes as $attr) {
                if (! isset($tags[$node->nodeName]) or ! in_array($attr->nodeName, $tags[$node->nodeName])) {
                    $attributes[] = $attr->nodeName;
                }
            }
        }

        /**
         * Remove attributes
         */
        foreach ($attributes as $attr) {
            $node->removeAttribute($attr);
        }

        /*
        # Add inserts first
        foreach ($change as list($a, $b)) {
            $b->parentNode->insertBefore($a, $b);
        }
        */

        # Now remove what we need
        foreach ($remove as $n) {
            if ($n->parentNode) {
                $n->parentNode->removeChild($n);
            }
        }
    }

    /**
     * Strips HTML tags and the content of those tags
     *
     * @param string $html
     * @param array $tags array of tags to strip, leave empty to strip all tags
     * @return string|null text or html
     */
    public static function stripTags(string $html, array $tags = []): ?string
    {
        $doc = new DOMDocument();
        /**
         * Html should not be modified in anyway
         */
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;
        @$doc->loadHTML($html, LIBXML_HTML_NODEFDTD);
        $remove = [];
        foreach ($tags as $tag) {
            $nodes = $doc->getElementsByTagName($tag);
            foreach ($nodes as $node) {
                $remove[] = $node;
            }
        }
        foreach ($remove as $node) {
            $node->parentNode->removeChild($node);
        }
        $content = preg_replace('~<(?:/?(?:html|body))[^>]*>\s*~i', '', $doc->saveHTML());

        return trim($content);
    }

    /**
     * Escapes Html for output in a secure way
     *
     * @param string $html
     * @param string $encoding
     * @return string
     */
    public static function escape(string $html, $encoding = 'UTF-8'): string
    {
        return htmlspecialchars($html, ENT_QUOTES, $encoding);
    }
}
