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
  * This extends the DOM extension, it gives the ability to use a javascript style query selectors
  * to find elements.
  */

namespace Origin\Utility;

use DOMDocument;
use DOMElement;

/**
  * This does the magic want it work like javascript querySelector, querySelectorAll.
  * It works with the following selectors:
  * - .class
  * - #id
  * - * all elements
  * - element e.g. h1
  * - element.class e.g. h1.heading  will return all the h1 elements with the class heading
  * - element,element - e.g div,p will select all divs and paragraphs
  * - element element - e.g. div p will select all p in a div
  * - [attribute=value] - e.g. a[target='_blank'] will select all links with target = _blank
  * - :first-child - e.g. p:first-child will select the first child element from the p
  * - :last-child - e.g. p:last-child will select the last child element from the p
  * - :nth-child(3) = e.g. p:3 will select the third child. 
  *
  * It currently does not cover complex selectors like div.highlighted > p for now. (~>+^|$).
  *
  * ## Examples
  *
  * $dom->querySelector('.className');
  * $dom->querySelector('h2.className');
  * $dom->querySelector('h2');
  * $dom->querySelector('#myId');
  * $dom->querySelector('section.content h1.heading span');
  * $dom->querySelector('div.main span:first-child');
  * $dom->querySelector('div.main span:last-child');
  * $dom->querySelector('div.main span:nth-child(3)');
  * $dom->querySelector('h1,h2');
  * $dom->querySelector('a[data-control-name="company-details"]);
  *
  * @todo
  * @see https://developer.mozilla.org/en-US/docs/Web/API/Document/querySelectorAll
  * @see https://www.w3schools.com/cssref/css_selectors.asp
  */
 trait QuerySelector
 {
     /**
         * Returns the first element that matches the specifiied selector or group of selectors.
        *
        * @param string $path
        * $dom->querySelector('.className');
        * $dom->querySelector('h2.className');
        * $dom->querySelector('h2');
        * $dom->querySelector('#myId');
        * $dom->querySelector('section.content h1.heading span');
        * $dom->querySelector('div.main span:first-child');
        * $dom->querySelector('div.main span:last-child');
        * $dom->querySelector('div.main span:nth-child(3)');
        * $dom->querySelector('h1,h2');
        * $dom->querySelector('a[data-control-name="company-details"]);
        * @param \DOMElement $dom
        * @return \DOMElement|null
        */
     public function querySelector(string $path)
     {
         $result = $this->runQuery($path, $this);
         if ($result) {
             return $result[0];
         }
         return null;
     }
     /**
      * Examples
      *
      * @param string $path
      * $dom->querySelectorAll('.className');
      * $dom->querySelectorAll('h2.className');
      * $dom->querySelectorAll('h2');
      * $dom->querySelectorAll('#myId');
      * $dom->querySelectorAll('section.content h1.heading span');
      * $dom->querySelectorAll('div.main span:first-child');
      * $dom->querySelectorAll('div.main span:last-child');
      * $dom->querySelectorAll('h1,h2');
      * $dom->querySelectorAll('a[data-control-name="company-details"]);
      * @return array
      */
     public function querySelectorAll(string $path)
     {
         return $this->runQuery($path);
     }

     /**
      * Handles multiple quries (seperated by commas)
      *
      * @param string $path
      * @return array
      */
     protected function multiQuery(string $path) : array
     {
         $jobs = explode(',', str_replace(', ', ',', $path));
         $results = [];
         foreach ($jobs as $job) {
             $results = array_merge($results, $this->querySelectorAll($job));
         }
         return $results;
     }

     /**
      * THis is the internal function do not call,
      * @internal it is callable since it
      * @param string $path
      * @return array
      */
     protected function runQuery(string $path, $dom =null) : array
     {
         $this->results = [];
       
         if ($dom === null) {
             $dom = $this;
         }

         // Handle EITHER div.note, div.alert??
         if (strpos($path, ',') !== false) {
             return $this->multiQuery($path);
         }
 
         $paths = explode(' ', $path); // convert .class1 .class2 into array
         $path = trim(array_shift($paths)); // get first time e.g. .class
         
         $class = null;
         // Handle without marker.
         if ($path[0] === '.') {
             $path = '*' . $path;
         }
         
         // Work with .class_name or H1
         if (strpos($path, '.') !== false) {
             list($tag, $class) = explode('.', $path);
         } else {
             $tag = $path;
         }
        
         // handle attribute selectors li[data-control-name="company-details"],[data-control-name="company-details"]
         $attrName = $attrValue = null;
         // Attribute selector: div.note, div.alert
         if (strpos($path, '[') !== false) {
             if ($path[0] === '[') {
                 $path = '*' . $path;
             }
            
             // Maybe change this to regex
             list($tag, $path) = explode('[', str_replace(']', '', $path));
             $path = str_replace(['"',"'"], '', $path);
             list($attrName, $attrValue) = explode('=', $path);
         }
 
         # Find Ids
         if ($path[0] === '#') {
             $tag = '*';
             $attrName = 'id';
             $attrValue = substr($path, 1);
         }
         
         // Handle last-child, first-child, nth etc
         if (strpos($path, ':') !== false) {
             list($tag, $n) =  explode(':', $path);
             $elements = $dom->getElementsByTagName($tag);
             if ($elements) {
                 // hanlde span:lastChild
                 if ($n === 'first-child') {
                     $this->results[] = $elements[0];
                 } elseif ($n === 'last-child') {
                     $this->results[] = $elements[count($elements)-1];
                 } elseif (preg_match('/nth-child\((\d+)\)/',$n,$matches)) {
                    if(isset($matches[1]) AND isset($elements[$matches[1]])){
                        $this->results[] = $elements[$matches[1]];
                    }             
                 }
             }
         } else {
             foreach ($dom->getElementsByTagName($tag) as $element) {
                 if ($attrName) {
                     if ($element->hasAttribute($attrName) and $element->getAttribute($attrName) === $attrValue) {
                         $this->results[]=  $element;
                     }
                 } elseif ($class === null or ($element->hasAttribute('class') and in_array($class, explode(' ', $element->getAttribute('class'))))) {
                     $this->results[] =  $element;
                 }
             }
         }

         # Go Next Level Down
         if ($paths) {
             foreach ($this->results as $result) {
                 $this->runQuery(implode(' ', $paths), $result);
             }
         }
         
         return $this->results;
     }
 }

/**
* Turbo charge the DOMDocument and DOM Element
* @internal dont want 2 extra files for a few lines of code
*/
class DomElementX extends \DOMElement
{
    use QuerySelector;
}

class Dom extends \DOMDocument
{
    use QuerySelector;

    public function __construct(string $version = null, string $encoding = null)
    {
        parent::__construct($version, $encoding);
        $this->registerNodeClass('DOMElement', DomElementX::class);
    }
}
