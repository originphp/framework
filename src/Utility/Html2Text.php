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
/**
 * A quick and dirty HTML to text convertor. It used by the Email Utility. Its almost markdown, the differences
 * being bold and images.
 */
namespace Origin\Utility;

use DOMDocument;

/**
 * @codeCoverageIgnore
 */
class Html2Text
{
    /**
     * Converts basic HTML into text format similar to markdown
     *
     * @param string $html
     * @return string
     */
    public static function convert(string $html) :string
    {
        deprecationWarning('Html2Text is being deprecated Html::toText instead');
        /**
         * PHP BUG
         *  $html = '<h1>Heading 1</h1><h2>Heading 2</h2><h3>Heading 3</h3>';
        * $xml = new DOMDocument();
        * @$xml->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        * $html = $xml->saveHTML(); // Clean HTML for parsing
        * debug($html); // gives <h1>Heading 1<h2>Heading 2</h2><h3>Heading 3</h3></h1>
         */
        $xml = new DOMDocument();
        @$xml->loadHTML($html, LIBXML_HTML_NODEFDTD); // bug  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
      
        $html = $xml->saveHTML(); // Clean HTML for parsing
    
        $html = preg_replace("[\r\n|\n|\r]", '\r\n', $html); // convert line endings to temporary marker

        $html = preg_replace('/(\>)\s*(\<)/m', '$1$2', $html); // remove white space between tags
        #@ to process blockquotes you need to add > before each <p>. split lines

        $html = preg_replace('/<p.*?>(.*?)<\/p>/i', "\n" . '$1' ."\n", $html); // add new line endings
        $html = preg_replace('/<br\s*\/?>/i', "<br />\n", $html); // <br> or <br />

        $html = preg_replace('/<\/th>/i', '</th> ', $html); // space after each th
        $html = preg_replace('/<\/td>/i', '</td> ', $html); // space after each td
        $html = preg_replace('/<\/tr>/i', "</tr>\n", $html); // space after each td

        $html = preg_replace('/<h1.*?>(.*?)<\/h1>/i', '# $1' ."\n\n", $html);
        $html = preg_replace('/<h2.*?>(.*?)<\/h2>/i', '## $1' ."\n\n", $html);
        $html = preg_replace('/<h3.*?>(.*?)<\/h3>/i', '### $1' ."\n\n", $html);
        $html = preg_replace('/<h4.*?>(.*?)<\/h4>/i', '#### $1' ."\n\n", $html);
        $html = preg_replace('/<h5.*?>(.*?)<\/h5>/i', '##### $1' ."\n\n", $html);
        $html = preg_replace('/<h6.*?>(.*?)<\/h6>/i', '###### $1' ."\n\n", $html);

        $html = preg_replace('/<li.*?>(.*?)<\/li>/i', '- $1' ."\n", $html);
        $html = preg_replace('/<strong.*?>(.*?)<\/strong>/i', '*$1*', $html);
        $html = preg_replace('/<em.*?>(.*?)<\/em>/i', '_$1_', $html);
        $html = preg_replace('/<i.*?>(.*?)<\/i>/i', '_$1_', $html);
       
        if (preg_match('/<blockquote.*?>(.*?)<\/blockquote>/i', $html, $matches)) {
            preg_match_all('/<blockquote.*?>(.*?)<\/blockquote>/i', $html, $matches);
            foreach ($matches as $match) {
                $replace = str_replace('\r\n', '</blockquote><blockquote>', $match[0]);
                $html = str_replace($match[0], $replace, $html);
            }
        }
        $html = preg_replace('/<blockquote.*?>(.*?)<\/blockquote>/', '  > $1' ."\n", $html);

        $html = preg_replace('/<code.*?>(.*?)<\/code>/', '`$1`' ."\n", $html);
        $html = preg_replace('/<dt.*?>(.*?)<\/dt>/i', '$1' ."\n", $html);
        $html = preg_replace('/<dd.*?>(.*?)<\/dd>/', ':  $1' ."\n", $html);
        $html = preg_replace('/&nbsp;/i', ' ', $html);
        $html = str_replace('\r\n', '', $html); // remove temporary marker

        //Loop through each <a> and </a> tag in the dom and add it to the link array
        foreach ($xml->getElementsByTagName('img') as $element) {
            $filename = $element->getAttribute('src');
            $string = "[image: {$filename}]";
            if ($element->hasAttribute('alt')) {
                $alt = $element->getAttribute('alt');
                $string = "[image: {$alt} {$filename}]";
            }
            $needle = $xml->saveHtml($element);
            $html = str_replace($needle, $string, $html);
        }

        foreach ($xml->getElementsByTagName('a') as $element) {
            $title = $element->textContent;
            $link = $element->getAttribute('href');
            if (preg_match('/(?:tel|skype|mailto):([^"]*)"/i', $link, $m)) {
                $link = $m[1]; # ?: non capture group
            }
            if ($element->hasAttribute('title')) {
                $title = $element->getAttribute('title');
            }
            $string = "[{$title}]($link)";
            $needle = $xml->saveHtml($element);
            $html = str_replace($needle, $string, $html);
        }

        $html = strip_tags($html);

        return rtrim($html);
    }
}
// @codeCoverageIgnoreEnd
