<?php
namespace App\Test\Lib;

use Origin\Utility\Html;

class HtmlTest extends \PHPUnit\Framework\TestCase
{
    public function testFromText()
    {
        $text = "This is a line of text without a newline";
        $expected = '<p>This is a line of text without a newline</p>';
        $this->assertEquals($expected, Html::fromText($text));

        $text = "This is a line of text without a newline";
        $expected = '<div>This is a line of text without a newline</div>';
        $this->assertEquals($expected, Html::fromText($text, ['tag'=>'div']));

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
<p>The end</p>
EOF;
        $expected ='6b875582453f7072eba7a36d32af626d';
        $this->assertEquals($expected, md5(Html::toText($html)));
        $expected = '9ae4a45439b47acddc1f2ba26ca24cc9';
        $this->assertEquals($expected, md5(Html::toText($html, ['format'=>false])));
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
}
