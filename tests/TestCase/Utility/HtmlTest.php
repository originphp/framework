<?php
namespace App\Test\Lib;

use Origin\Utility\Html;

class HtmlTest extends \PHPUnit\Framework\TestCase
{
    public function testFromText()
    {
        $text = 'This is a line of text without a newline';
        $expected = '<p>This is a line of text without a newline</p>';
        $this->assertEquals($expected, Html::fromText($text));

        $text = 'This is a line of text without a newline';
        $expected = '<div>This is a line of text without a newline</div>';
        $this->assertEquals($expected, Html::fromText($text, ['tag' => 'div']));

        $expected = '<p>This is testing a line<br>with a line break.</p>';
        $text = "This is testing a line\nwith a line break.";
        $this->assertEquals($expected, Html::fromText($text));
        $text = "This is testing a line\r\nwith a line break.";
        $this->assertEquals($expected, Html::fromText($text));

        $expected = "<p>The quick brown fox</p>\n<p>jumped over the lazy dog</p>";
        $text = "The quick brown fox\n\njumped over the lazy dog";
        $this->assertEquals($expected, Html::fromText($text));
        $text = "The quick brown fox\r\n\r\njumped over the lazy dog";
        $this->assertEquals($expected, Html::fromText($text));

        $expected = "<p>line 1</p>\n<p>line 2<br>line 3</p>";
        $text = "line 1\n\nline 2\nline 3";
        $this->assertEquals($expected, Html::fromText($text));
        $text = "line 1\r\n\r\nline 2\r\nline 3";
        $this->assertEquals($expected, Html::fromText($text));
    }

    public function testMinify()
    {
        $html = <<< EOF
<h1>
Heading #1
</h1>
<h2>Heading #2</h2>
<p>
Lorem ipsum dolor sit amet, consectetur adipiscing elit.    Nulla vitae lobortis diam. Nam    porta magna nec porttitor bibendum. Vestibulum tristique lorem in urna hendrerit, et 
commodo velit suscipit. Sed imperdiet tincidunt condimentum. Aliquam erat volutpat. Cras rhoncus mauris at enim ultrices, sed consequat lectus aliquam. Nullam venenatis porta quam, sit amet 
pulvinar felis porttitor ut. Morbi vel vestibulum mi. Vestibulum id erat tortor. Integer ac semper elit.

</p>
<p>Use <a href="https://www.google.com">Google</a> to do some searches.</p>
<ul>
    <li>
    List #1
    </li>
    <li>List #1</li>
</ul>
<div><img src="https://www.google.com/img/logo.png"></div>
<blockquote>Life is what happens when you're busy making other plans.</blockquote>
<div class="foo">Lorem <strong>ipsum</strong> <em>dolor</em> sit amet, <span>consectetur adipiscing</span> elit.</span></div>
<pre>
<code>
Csv::load('somefile.csv');
Csv::toArray(myvar);
</code>
</pre>
EOF;

        $expected = <<< EOF
<html><body><h1>Heading #1</h1><h2>Heading #2</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla vitae lobortis diam. Nam porta magna nec porttitor bibendum. Vestibulum tristique lorem in urna hendrerit, et commodo velit suscipit. Sed imperdiet tincidunt condimentum. Aliquam erat volutpat. Cras rhoncus mauris at enim ultrices, sed consequat lectus aliquam. Nullam venenatis porta quam, sit amet pulvinar felis porttitor ut. Morbi vel vestibulum mi. Vestibulum id erat tortor. Integer ac semper elit.</p><p>Use <a href="https://www.google.com">Google</a> to do some searches.</p><ul><li> List #1 </li><li>List #1</li></ul><div><img src="https://www.google.com/img/logo.png"></div><blockquote>Life is what happens when you're busy making other plans.</blockquote><div class="foo">Lorem <strong>ipsum</strong> <em>dolor</em> sit amet, <span>consectetur adipiscing</span> elit.</div><pre>
<code>
Csv::load('somefile.csv');
Csv::toArray(myvar);
</code>
</pre></body></html>
EOF;
        $this->assertEquals($expected, html::minify($html));
    }

    /**
     * I put markers to track the line endings
     *
     * @return void
     */
    public function testToTextHeadings()
    {
        $html = '<span>*</span><h1>Search Engines</h1><span>*</span>';
        $expected = "\nSearch Engines\n==============";
        $this->assertContains($expected, Html::toText($html));

        $html = '<span>*</span><h2>Heading Number 2</h2><span>*</span>';
        $expected = "\nHeading Number 2\n----------------";
        $this->assertContains($expected, Html::toText($html));

        $html = '<span>*</span><h3>Heading Number 3</h3><span>*</span>';
        $expected = "\nHeading Number 3\n----------------";
        $this->assertContains($expected, Html::toText($html));

        $html = '<span>*</span><h4>Heading Number 4</h4><span>*</span>';
        $expected = "\nHeading Number 4\n----------------";
        $this->assertContains($expected, Html::toText($html));

        $html = '<span>*</span><h5>Heading Number 5</h5><span>*</span>';
        $expected = "\nHeading Number 5\n----------------";
        $this->assertContains($expected, Html::toText($html));

        $html = '<span>*</span><h6>Heading Number 6</h6><span>*</span>';
        $expected = "\nHeading Number 6\n----------------";
        $this->assertContains($expected, Html::toText($html));
    }

    public function testToTextBlockquote()
    {
        $html = '<span>*</span><blockquote>Life is what happens to you whilst you are busy planning it</blockquote><span>*</span>';
        $expected = "\n\"Life is what happens to you whilst you are busy planning it\"\n";
        $this->assertContains($expected, Html::toText($html));
    }

    public function testToTextImage()
    {
        $html = '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Google_2015_logo.svg/220px-Google_2015_logo.svg.png">';
        $expected = '[image: https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Google_2015_logo.svg/220px-Google_2015_logo.svg.png]';
        $this->assertContains($expected, Html::toText($html));

        $html = '<img alt="Google Logo" src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Google_2015_logo.svg/220px-Google_2015_logo.svg.png">';
        $expected = '[image: Google Logo https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Google_2015_logo.svg/220px-Google_2015_logo.svg.png]';
        $this->assertContains($expected, Html::toText($html));

        // Test with special characters
        $html = '<img alt="Beavis & Butt-Head" src="https://en.wikipedia.org/wiki/Beavis_and_Butt-Head#/media/File:Beavis_and_Butt-head_titlecard.png?cache=clear&id=1234">';
        $expected = '[image: Beavis & Butt-Head https://en.wikipedia.org/wiki/Beavis_and_Butt-Head#/media/File:Beavis_and_Butt-head_titlecard.png?cache=clear&id=1234]';
        $this->assertContains($expected, Html::toText($html));
    }

    public function testToTextLinks()
    {
        $html = '<a href="https://www.google.com">Google</a>';
        $expected = '[Google](https://www.google.com)';
        $this->assertContains($expected, Html::toText($html));

        $html = '<a href="https://www.google.com/search?q=some_underscored_keyword&results=100">Number #1 Search Engine & Favourite</a>';
        $expected = '[Number #1 Search Engine & Favourite](https://www.google.com/search?q=some_underscored_keyword&results=100)';
        $this->assertContains($expected, Html::toText($html));
    }

    public function testToTextPargraph()
    {
        /**
         * Test various things from line breaks and extra spaces in between p tags
         */
        $html = '<span>*</span><p>Emphasis,    aka italics, 
        with <em>asterisks</em> or <em>underscores</em>.<br>Strong 
        emphasis, aka bold, with <strong>asterisks</strong> or <strong>underscores</strong>.<br>Combined 
        emphasis with <strong>asterisks and <em>underscores</em></strong>.<br>Strikethrough uses 
        two tildes. <del>Scratch this.</del>
        </p><span>*</span>';

        $expected = <<< EOF
Emphasis, aka italics, with asterisks or underscores.
Strong emphasis, aka bold, with asterisks or underscores.
Combined emphasis with asterisks and underscores.
Strikethrough uses two tildes. Scratch this. 
EOF;
        $this->assertContains($expected, Html::toText($html));

        $html = '<span>%</span><p>You can use the <a href="https://github.com/googleapis/google-api-php-client/tree/master/examples">Google API</a> to access various Google services.</p><span>%</span>';
        $expected = "\nYou can use the [Google API](https://github.com/googleapis/google-api-php-client/tree/master/examples) to access various Google services.\n";
        
        $this->assertContains($expected, Html::toText($html));
    }

    public function testToTextUnorderedList()
    {
        $html = '<span>%</span><ul><li>Item #1</li><li>Item #2</li></ul><span>%</span>';
        $expected = "\n* Item #1\n* Item #2\n";
        $this->assertContains($expected, Html::toText($html));
    }

    public function testToTextNumberedList()
    {
        $html = '<span>%</span><ol><li>Item #1</li><li>Item #2</li></ol><span>%</span>';
        $expected = "\n1. Item #1\n2. Item #2\n";
        $this->assertContains($expected, Html::toText($html));
    }

    public function testToTextSubList()
    {
        $html = '<h2>A Nested List</h2><p>List can be nested (lists inside lists):</p><ul> <li>Coffee</li><li>Tea<ul> <li>Black tea</li><li>Green tea</li></ul></li> <li>Milk</li></ul>';

        $expected = "\n* Coffee\n* Tea\n   * Black tea\n   * Green tea\n* Milk";
        $this->assertContains($expected, Html::toText($html));
    }

    public function testToTextSubListOrdered()
    {
        $html = '<h2>A Nested List</h2><p>List can be nested (lists inside lists):</p><ul> <li>Coffee</li><li>Tea<ol> <li>Black tea</li><li>Green tea</li></ol></li> <li>Milk</li></ul>';

        $expected = "\n* Coffee\n* Tea\n   1. Black tea\n   2. Green tea\n* Milk";
        $this->assertContains($expected, Html::toText($html));
    }

    public function testToTextTables()
    {
        $html = '<span>%</span><table><tr> <th>Revenue</th> <th>31/12/2018</th> <th>31/12/2017</th> <th>31/12/2016</th></tr><tr> <td>Total revenue</td><td>136,819,000</td><td>110,855,000</td><td>90,272,000</td></tr><tr> <td>Cost of revenue</td><td>59,549,000</td><td>45,583,000</td><td>35,138,000</td></tr><tr> <td>Gross profit</td><td><strong>77,270,000</strong></td><td><strong>65,272,000</strong></td><td><strong>55,134,000</strong></td></tr></table><span>%</span>';

        $expected = <<< EOF
+------------------+--------------+--------------+-------------+
| Revenue          | 31/12/2018   | 31/12/2017   | 31/12/2016  |
+------------------+--------------+--------------+-------------+
| Total revenue    | 136,819,000  | 110,855,000  | 90,272,000  |
| Cost of revenue  | 59,549,000   | 45,583,000   | 35,138,000  |
| Gross profit     | 77,270,000   | 65,272,000   | 55,134,000  |
+------------------+--------------+--------------+-------------+
EOF;
        $this->assertContains($expected, Html::toText($html));
    }

    public function testToTextCode()
    {
        $html = <<< EOF
<span>%</span>
<pre>
<code>$ composer require orignphp/framework</code>
</pre>
<span>%</span>
EOF;
        $expected = "\n$ composer require orignphp/framework\n";
        $this->assertContains($expected, Html::toText($html));

        $html = <<< EOF
<span>%</span>
<pre><code>require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}
... truncated
</code></pre>
<span>%</span>
EOF;

        $expected = "\n   require __DIR__ . '/vendor/autoload.php';\n   \n   if (php_sapi_name() != 'cli') {\n       throw new Exception('This application must be run on the command line.');\n   }\n   ... truncated\n   \n";

        $this->assertContains($expected, Html::toText($html));
    }

    /**
     * This was original test
     *
     * @return void
     */
    public function testToText()
    {
        $html = <<< EOF
<h1>Search Engines</h1>
<h2>Google</h2><h3>About</h3>
<blockquote>Google is not a conventional company. We do not intend to become one.</blockquote>
<p>Google LLC is an American        multinational technology 
company that specializes in Internet-related services and products, which include online advertising technologies, search engine, cloud computing, software, and hardware.<br>It is considered one of the Big Four technology companies, alongside Amazon, Apple and Facebook</p>
<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Google_2015_logo.svg/220px-Google_2015_logo.svg.png">
<p>Benefits of using Google:</p>
<ol>
    <li>Good quality search results</li>
    <li>Relevent advertising</li>
</ol>
<p>Important links:</p>
<ul>
    <li><a href="https://en.wikipedia.org/wiki/Google">Google's Wikipedia Page</a></li>
    <li><a href="https://abc.xyz/">Alphabet</a></li>
</ul>
<h3>Financial Results</h3>
<p>Below are the <span>financial</span> results for the last <em>3 years</em>.</p>
<table>
<tr>
        <th>Revenue</th>
        <th>31/12/2018</th>
        <th>31/12/2017</th>
        <th>31/12/2016</th>
</tr>
<tr>
        <td>Total revenue</td>
        <td>136,819,000</td>
        <td>110,855,000</td>
        <td>90,272,000</td>
</tr>
<tr>
        <td>Cost of revenue</td>
        <td>59,549,000</td>
        <td>45,583,000</td>
        <td>35,138,000</td>
</tr>
<tr>
        <td>Gross profit</td>
        <td><strong>77,270,000</strong></td>
        <td><strong>65,272,000</strong></td>
        <td><strong>55,134,000</strong></td>
</tr>
</table>
<h3>Using Google API</h3>
<p>You can use the <a href="https://github.com/googleapis/google-api-php-client/tree/master/examples">Google API</a> to access various Google services.</p>
<p>To install the library:</p>
<pre class="devsite-click-to-copy">
<code class="devsite-terminal">composer require google/apiclient:^2.0</code>
</pre>
<p>Create a file called <code>quickstart.php</code> and add the following contents</p>
<pre><code>require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}
... truncated
</code></pre>
<p>Emphasis, aka italics, with <em>asterisks</em> or <em>underscores</em>.<br>Strong emphasis, aka bold, with <strong>asterisks</strong> or <strong>underscores</strong>.<br>Combined emphasis with <strong>asterisks and <em>underscores</em></strong>.<br>Strikethrough uses two tildes. <del>Scratch this.</del></p>
EOF;
        $expected = 'e58d553654c188ef178192cbdd3859ad';
 
        $this->assertEquals($expected, md5(Html::toText($html)));
       
        // Non Format version
        $expected = '89f7e9caa94a499e332848dba8cdac15';
        $this->assertEquals($expected, md5(Html::toText($html, ['format' => false])));
    }

    public function testStripTagsSelected()
    {
        $html = <<< EOF
<h1>Heading</h1>
<div>
    <p>Some text in a <strong>div</strong></p>
    <script>alert('hello');</script>
    <iframe>nasty</iframe>
</div>
EOF;
        $expected = <<< EOF
<h1>Heading</h1>
<div>
    <p>Some text in a <strong>div</strong></p>
    
    
</div>
EOF;
        $this->assertEquals($expected, Html::stripTags($html, ['script','iframe']));
    }

    public function testSanitize()
    {
        $html = '<div><h1 class="foo">Heading</h1><a href="https:/www.google.com" class="link">Google</a><img src="javascript:alert(\'hello\);"><div>';
        $tags = ['div','h1','a' => ['class','style']];
        // link href removed (Attribute)
        // image completely removed (element)
        $this->assertEquals('<div><h1>Heading</h1><a class="link">Google</a><div></div></div>', Html::sanitize($html, $tags));
    }
}
