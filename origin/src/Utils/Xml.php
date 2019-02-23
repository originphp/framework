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

use DOMDocument;
use DOMElement;
use SimpleXMLElement;
use Origin\Utils\Exception\XmlException;
use Origin\Core\Configure;

/**
 * The XML utility is for converting arrays to XML strings and XML strings to arrays.
 * Does not support namespaes
 */
class Xml
{
 
    /**
     * Creates an XML block of elements. If you need custom xml and fromArray does not cut it
     * then use this.
     *
     * $xml = Xml::create('post', [
     *            '@category' => 'how tos', // add @ to set the attribute
     *            'id' => 12345,
     *            'title' => 'How to create an XML block',
     *            'body' =>  Xml::cdata(' ... ')
     *          ]);
     *
     * This will give you something like this
     *
     * <post category="how tos"><id>12345</id><title>How to create an XML block</title>
     * <body>&lt;![CDATA["A quick brown fox jumps of a lazy dog."]]&gt;</body></post>
     *
     * @param string $name
     * @param array $options
     * @return string
     */
    public static function create(string $name, array $params) : string
    {
        $dom = new DOMDocument();
        $root = $dom->createElement($name);
       
        static::convertArray($dom, $root, $params);
       
        $dom->appendChild($root);
        return $dom->saveXML($root);
    }

    /**
     * Converts an array into an XML string, you can also pass an array of options.
     *
     *  $data = [
     *       'post' => [
     *           '@category' => 'how tos', // to set attribute use @
     *           'id' => 12345,
     *           'title' => 'How to create an XML block',
     *           'body' =>  Xml::cdata('A quick brown fox jumps of a lazy dog.'),
     *           'author' => [
     *              'name' => 'James'
     *            ]
     *          ]
     *     ];
     *
     *  $xml = Xml::fromArray($data,[
     *      'version' => '1.0',
     *      'encoding' => 'UTF-8'
     *   ]);
     *
     *  You can also set a value of an element using @ as the key.
     *
     *  Sometimes when working with xml, some tags are repeated.
     *
     * @param array $data
     * @param array $options
     * @return void
     */
    public static function fromArray(array $data, array $options=[]) : string
    {
        // Check its valid
        if (count($data) !==1) {
            throw new XmlException('Array must have only one element.');
        }
        $defaults = [
            'version' => '1.0',
            'encoding' => Configure::read('App.encoding'),
            'pretty' => false
        ];
        $options += $defaults;
      
        $dom = new DOMDocument($options['version'], $options['encoding']);
        if ($options['pretty']) {
            $dom->formatOutput = true;
            $dom->preserveWhitespace = false;
        }
    
        static::convertArray($dom, $dom, $data);

        return  $dom->saveXML();
    }

    /**
     * Is is the workhorse for the fromArray
     *
     * @param DOMDocument $dom
     * @param DOMDocument/DOMElement $parent
     * @param array $data
     * @return void
     */
    protected static function convertArray(DOMDocument $dom, $parent, array $data) : void
    {
        $tags = [];
        // Deal with attributes/values
        foreach ($data as $key => $value) {
            if ($key === '@') {
                $parent->appendChild($dom->createTextNode($data['@']));
            } elseif ($key[0] === '@') {
                $key = substr($key, 1);
                $parent->setAttribute($key, $value);
            } else {
                $tags[$key] = $value;
            }
        }
      
        // Create tags
        foreach ($tags as $key => $value) {
            if (is_array($value)) {
                // handle repeated tags
                if (is_numeric(implode('', array_keys($value)))) {
                    foreach ($value as $repeated) {
                        $node = $dom->createElement($key);
                        $parent->appendChild($node);
                        static::convertArray($dom, $node, $repeated);
                    }
                    continue;
                }
                if (is_integer($key)) {
                    throw new XmlException('Invalid array.');
                }
                $node = $dom->createElement($key);
                $parent->appendChild($node);
                static::convertArray($dom, $node, $value);
            } else {
                $element = $dom->createElement($key);
                if ($value !== null) {
                    $element->nodeValue = $value;
                }
                $parent->appendChild($element);
            }
        }
    }

    /**
     * Wraps a string in CDATA
     *
     * @param string|integer $value
     * @return string
     */
    public static function cdata($value) : string
    {
        return '<![CDATA["'. $value .'"]]>';
    }

    /**
     * Converts an array into XML
     *
     * @param string $xml
     * @return void
     */
    public static function toArray(string $xml) : array
    {
        $simpleXml = @simplexml_load_string($xml);
        if ($simpleXml) {
            $root = static::convertXml($simpleXml);
            return [$simpleXml->getName()=>$root];
        }
        throw new XmlException('Invalid XML.');
    }

    protected static function convertXml(SimpleXMLElement $xml)
    {
        $data = [];
        
        foreach ($xml->attributes() as $key => $value) {
            $data['@' . $key] = (string) $value;
        }
       
        foreach ($xml->children() as $child) {
            $name = $child->getName();
            $array = static::convertXml($child);
            if (isset($data[$name])) {
                if (!is_array($data[$name]) or !isset($data[$name][0])) {
                    $data[$name] = [$data[$name]];
                }
                $data[$name][] = $array;
            } else {
                $data[$name] = $array;
            }
        }

        $string = (string) $xml;
        if (empty($data)) {
            return $string;
        }
        
        if (strlen($string) > 0) {
            $data['@'] = $string;
        }

        return $data;
    }
}
