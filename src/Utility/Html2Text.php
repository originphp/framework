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
 * I wrote this a few years ago,
 */
namespace Origin\Utility;

class Html2Text
{
    public static function convert($html)
    {
        $html = preg_replace("[\r\n|\n|\r]", '', $html); // remove all line endings

        $html = preg_replace('/(\>)\s*(\<)/m', '$1$2', $html); // remove white space between tags
        #@ to process blockquotes you need to add > before each <p>. split lines
        $html = preg_replace('/<p>/i', "\n<p>", $html);  // add new line endings
        $html = preg_replace('/<p /i', "\n<p ", $html);  // add new line endings
        $html = preg_replace('/<\/p>/i', "</p>\n", $html);  // add new line endings
        $html = preg_replace('/<br\s*\/?>/i', "<br />\n", $html); // <br> or <br />

        $html = preg_replace('/<\/th>/i', '</th> ', $html); // space after each th
        $html = preg_replace('/<\/td>/i', '</td> ', $html); // space after each td
        $html = preg_replace('/<\/tr>/i', "</tr>\n", $html); // space after each td

        $html = preg_replace('/<\/h1>/i', "</h1>\n\n", $html); // space after each h1
        $html = preg_replace('/<h1>/i', "# <h1>", $html); // space after each h1

        $html = preg_replace('/<\/h2>/i', "</h2>\n\n", $html); // space after each h2
        $html = preg_replace('/<h2>/i', "## <h2>", $html); // space after each h12

        $html = preg_replace('/<\/h3>/i', "</h3>\n\n", $html); // space after each h2
        $html = preg_replace('/<h3>/i', "### <h3>", $html); // space after each h1

        $html = preg_replace("/&nbsp;/i", ' ', $html);

        # Change <strong>Name</strong> to *Name*
        if (preg_match_all('/<strong>([^>]*)<\/strong>/i', $html, $matches)) {
            foreach ($matches[0] as $i => $needle) {
                $html = str_replace($needle, '*' . $matches[1][$i] . '*', $html);
            }
        }

        # Change Italic
        if (preg_match_all('/<em>([^>]*)<\/em>/i', $html, $matches)) {
            foreach ($matches[0] as $i => $needle) {
                $html = str_replace($needle, '_' . $matches[1][$i] . '_', $html);
            }
        }

        # Change images to markers using alt e.g [image: some image title from alt]
        if (preg_match_all('/<img\s[^>]*>/i', $html, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $alt = $filename = '';
                if (preg_match('/alt ?= ?"([^"]*)"/i', $match, $m)) {
                    $alt = $m[1];
                }
                if (preg_match('/src ?= ?"([^"]*)"/i', $match, $m)) {
                    $filename = $m[1];
                }
                $string = "[image: {$filename}]";
                if ($alt) {
                    $string = "[image: {$alt} {$filename}]";
                }
                $html = str_replace($match, $string, $html);
            }
        }

        /**
         * All links now captured
         */
        if (preg_match_all('/<a\s[^>]*href\s*=\s*(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>/siU', $html, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $link = $title = '';

                if (preg_match('/<a\s[^>]*>(.*)<\/a>/siU', $match, $m)) {
                    $title = $m[1];
                }

                if (preg_match('/href ?= ?"(?:tel|skype|mailto):([^"]*)"/i', $match, $m)) {
                    $link = $m[1]; # ?: non capture group
                }

                if (preg_match('/href ?= ?"([^"]*)"/i', $match, $m)) {
                    $link = $m[1];
                }

                if (preg_match('/title ?= ?"([^"]*)"/i', $match, $m)) {
                    $title = $m[1];
                }
                $string = $link;
                if ($title) {
                    $string = "[{$title}]($link)";
                }
                $html = str_replace($match, $string, $html);
            }
        }
        $html = rtrim($html);
        return strip_tags($html);
    }
}
