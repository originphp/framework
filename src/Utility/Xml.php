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

namespace Origin\Utility;

use DOMDocument;
use SimpleXMLElement;
use Origin\Core\Configure;
use Origin\Utility\Exception\XmlException;

/**
 * The XML utility is for converting arrays to XML strings and XML strings to arrays.
 */
class Xml
{
 
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
     *      'encoding' => 'UTF-8',
     *      'pretty' => true
     *   ]);
     *
     *  You can also set the text value of an element using @ as the key.
     *
     *
     * @param array $data
     * @param array $options
     * @return void
     */
    public static function fromArray(array $data, array $options = []) : string
    {
        // Must have root
        if (count($data) !== 1) {
            throw new XmlException('Invalid array.');
        }
        $defaults = [
            'version' => '1.0',
            'encoding' => Configure::read('App.encoding'),
            'pretty' => false,
        ];
        $options += $defaults;
      
        $dom = new DOMDocument($options['version'], $options['encoding']);
        if ($options['pretty']) {
            $dom->formatOutput = true;
            $dom->preserveWhitespace = false;
        }
    
        static::convertArray($dom, $dom, $data);

        return $dom->saveXML();
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
                $parent->appendChild($dom->createTextNode((string) $data['@']));
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
                        if (is_string($repeated)) {
                            $repeated = ['@' => $repeated];
                        }
                        $node = $dom->createElement($key);
                        $parent->appendChild($node);
                        static::convertArray($dom, $node, $repeated);
                    }
                    continue;
                }
               
                $node = $dom->createElement($key);

                if (isset($value['xmlns:'])) {
                    $node->setAttribute('xmlns', $value['xmlns:']);
                    unset($value['xmlns:']);
                }

                $parent->appendChild($node);
                static::convertArray($dom, $node, $value);
            } else {
                if (strpos($key, 'xmlns:') !== false) {
                    $parent->setAttributeNS('http://www.w3.org/2000/xmlns/', $key, $value); # @see http://php.net/manual/en/domelement.setattributens.php
                    continue;
                }

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
        try {
            $simpleXml = new SimpleXMLElement($xml);
        } catch (\Exception $e) {
            throw new XmlException($e->getMessage());
        }
        
        $namespaces = $simpleXml->getNamespaces(true);
        $root = static::convertXml($simpleXml, null, array_merge([null => null], $namespaces));
   
        return [
            static::getRootName($simpleXml) => $root,
        ];
    }

    /**
     * Determine the root name with namespace prefix
     * A generic namespace will return a key of ''
     * Multiple namespaces defined will only return the actual namespace
     *
     * @param SimpleXMLElement $simpleXml
     * @return string
     */
    protected static function getRootName(SimpleXMLElement $simpleXml) : string
    {
        $name = $simpleXml->getName();
        $namespaces = $simpleXml->getNamespaces();
        if ($namespaces and ! isset($namespaces[''])) {
            $prefix = key($namespaces);
            $name = "{$prefix}:{$name}";
        }

        return $name;
    }

    /**
     * issue with getting root namespace
     *
     * @param SimpleXMLElement $xml
     * @param [type] $currentNamespace
     * @param array $namespaces
     * @return array|string
     */
    protected static function convertXml(SimpleXMLElement $xml, $currentNamespace, array $namespaces = [])
    {
        $data = [];

        // Handle Generic Namespace
        if ($currentNamespace === null and $namespaces[$currentNamespace]) {
            $data['xmlns:'] = $namespaces[$currentNamespace];
        }
       
        foreach ($namespaces as $namespace => $xmlns) {
            foreach ($xml->attributes($namespace, true) as $key => $value) {
                $data['@' . $key] = (string) $value;
            }
            foreach ($xml->children($namespace, true) as $child) {
                $array = static::convertXml($child, $namespace, $namespaces);
                
                $name = $child->getName();
           
                if ($namespace) {
                    $name = "{$namespace}:{$name}";
                    $data['xmlns:' . $namespace] = $xmlns;
                }
                if (isset($data[$name])) {
                    if (! is_array($data[$name]) or ! isset($data[$name][0])) {
                        $data[$name] = [$data[$name]]; // repackage repeated tags
                    }
                    $data[$name][] = $array;
                } else {
                    $data[$name] = $array;
                }
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
