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

namespace Origin\Test\Utility;

use Origin\Utility\Dom;

/**
 * @property \Lib\Dom $Dom
 */
class DomTest extends \PHPUnit\Framework\TestCase
{
    public function testSelectorTag()
    {
        $html = '<div><h1 class="heading">Hello World!</h1><h2 class="heading">It is a test</h2></div>';
        $dom = new Dom();
        $dom->loadHtml($html);
        $this->assertEquals('It is a test', $dom->querySelector('h2')->textContent);
        $this->assertEquals('It is a test', $dom->querySelectorAll('h2')[0]->textContent);
    }
    public function testSelectorClass()
    {
        $html = '<div><h1 class="heading">Hello World!</h1><h2 class="heading">It is a test</h2></div>';
        $dom = new Dom();
        $dom->loadHtml($html);
        $this->assertEquals('Hello World!', $dom->querySelector('.heading')->textContent);
        $this->assertEquals('It is a test', $dom->querySelector('h2.heading')->textContent);
    }
    public function testSelectorId()
    {
        $html = '<div class="bootstrap"><div id="foo">x</div></div>';
        $dom = new Dom();
        $dom->loadHtml($html);
        $this->assertEquals('x', $dom->querySelector('#foo')->textContent);
    }

    public function testSelectorAttribute()
    {
        $html = '<div class="somewhere"><a href="https://www.yahoo.com">Yahoo</a><a data-control-name="company-details" href="https://www.google.com">Google</a></div>';
        $dom = new Dom();
        $dom->loadHtml($html);
   
        $this->assertEquals('https://www.google.com', $dom->querySelector('[data-control-name="company-details"]')->getAttribute('href'));
        $this->assertEquals('https://www.google.com', $dom->querySelector('a[data-control-name="company-details"]')->getAttribute('href'));
    }

    public function testSelectorElement()
    {
        $html = '<div><h1 class="heading">Hello World!</h1><h2 class="heading">It is a test</h2></div>';
        $dom = new Dom();
        $dom->loadHtml($html);

        $element = $dom->querySelector('div');
        $this->assertEquals('It is a test', $element->querySelector('h2.heading')->textContent);
    }
    
    public function testQuerySelectorMulti()
    {
        $html = '<div class="main"><div class="sub"><h1>Hello</h1><p>Some text</p></div></div>';
        $dom = new Dom();
        $dom->loadHtml($html);
        $this->assertEquals('Hello', $dom->querySelector('div.main div.sub h1')->textContent);
        $this->assertNull($dom->querySelector('div.foo div.sub h1'));
        $this->assertNull($dom->querySelector('div.main div.foo h1'));
    }

    public function testQuerySelectorChildren()
    {
        $html = '<div class="main"><span>one</span><span>two</span><span>three</span></div>';
        $dom = new Dom();
        $dom->loadHtml($html);
        $this->assertEquals('three', $dom->querySelector('div.main span:last-child')->textContent);
        $this->assertEquals('one', $dom->querySelector('div.main span:first-child')->textContent);
        $this->assertEquals('two', $dom->querySelector('div.main span:nth-child(1)')->textContent);
        $this->assertEquals('three', $dom->querySelector('div.main span:nth-child(2)')->textContent);
        $this->assertNull($dom->querySelector('div.main span:10'));
    }
 
    public function testSelectorEither()
    {
        $html = '<div class="warning"><div class="error">Error</div><div class="foo">Nothing</div><div class="message">Message</div></div>';
        $dom = new Dom();
        $dom->loadHtml($html);
        $result = $dom->querySelectorAll('div.error,div.message');
        $this->assertEquals('Error', $result[0]->textContent);
        $this->assertEquals('Message', $result[1]->textContent);

        $result = $dom->querySelectorAll('div.error, div.message');
        $this->assertEquals('Error', $result[0]->textContent);
        $this->assertEquals('Message', $result[1]->textContent);
    }

    /**
    * This was a bug due to refactoring, no recursion was happening
    * and as a result all spans we being found from root.
    *
    */
    public function testDebug()
    {
        $html = '
        <div class="main">
            <div class="abc"><h4><span>one</span><span>two</span></h4></div>
            <div class="def"><h4><span>three</span><span>four</span></h4></div>
            <div class="ghi"><h4><span>four</span><span>five</span></h4></div>
         </div>';
        $dom = new Dom();
        $dom->loadHtml($html);
        $result = $dom->querySelectorAll('div.main div.def span:last-child');
        $this->assertEquals(1, count($result));
        $this->assertEquals('four', $result[0]->textContent);
    }
}
