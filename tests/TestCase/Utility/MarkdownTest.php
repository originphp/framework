<?php

namespace App\Test\Lib;

use Origin\Utility\Markdown;

class MarkdownTest extends \PHPUnit\Framework\TestCase
{
    public function testToText()
    {
        $text = <<< EOF
# Search Engines

## Google

### About

> Google is not a conventional company. We do not intend to become one.

*Google LLC* is an _American multinational technology_ company that specializes in Internet-related services and products, which include online advertising technologies, search engine, cloud computing, software, and hardware.
It is considered one of the Big Four technology companies, alongside Amazon, Apple and Facebook
![](https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Google_2015_logo.svg/220px-Google_2015_logo.svg.png)
Benefits of using Google:

1. Good quality search results
2. Relevent advertising

Important links:

* [Google's Wikipedia Page](https://en.wikipedia.org/wiki/Google)
* [Alphabet](https://abc.xyz/)

### Financial Results

Below are the financial results for the last 3 years.

| Revenue          | 31/12/2018   | 31/12/2017   | 31/12/2016  |
|------------------|--------------|--------------|-------------|
| Total revenue    | 136,819,000  | 110,855,000  | 90,272,000  |
| Cost of revenue  | 59,549,000   | 45,583,000   | 35,138,000  |
| Gross profit     | 77,270,000   | 65,272,000   | 55,134,000  |

### Using Google API

You can use the [Google API](https://github.com/googleapis/google-api-php-client/tree/master/examples) to access various Google services.

To install the library:

`composer require google/apiclient:^2.0`

Create a file called `quickstart.php` and add the following contents

```
require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}
... truncated
```

Emphasis, aka italics, with *asterisks* or _underscores_.
Strong emphasis, aka bold, with **asterisks** or __underscores__.
Combined emphasis with **asterisks and _underscores_**.
Strikethrough uses two tildes. ~~Scratch this.~~
EOF;
        $expected = 'a9529bcba792944b1b8a982d5231907d';
        $this->assertEquals($expected, md5(Markdown::toText($text)));
    }

    public function testToHtml()
    {
        $text = <<< EOF
# Search Engines

## Google

### About

> Google is not a conventional company. We do not intend to become one.

*Google LLC* is an _American multinational technology_ company that specializes in Internet-related services and products, which include online advertising technologies, search engine, cloud computing, software, and hardware.
It is considered one of the Big Four technology companies, alongside Amazon, Apple and Facebook
![](https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Google_2015_logo.svg/220px-Google_2015_logo.svg.png)
Benefits of using Google:

1. Good quality search results
2. Relevent advertising

Important links:

* [Google's Wikipedia Page](https://en.wikipedia.org/wiki/Google)
* [Alphabet](https://abc.xyz/)

### Financial Results

Below are the financial results for the last 3 years.

| Revenue          | 31/12/2018   | 31/12/2017   | 31/12/2016  |
|------------------|--------------|--------------|-------------|
| Total revenue    | 136,819,000  | 110,855,000  | 90,272,000  |
| Cost of revenue  | 59,549,000   | 45,583,000   | 35,138,000  |
| Gross profit     | 77,270,000   | 65,272,000   | 55,134,000  |

### Using Google API

You can use the [Google API](https://github.com/googleapis/google-api-php-client/tree/master/examples) to access various Google services.

To install the library:

`composer require google/apiclient:^2.0`

Create a file called `quickstart.php` and add the following contents

```
require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}
... truncated
```
        
Emphasis, aka italics, with *asterisks* or _underscores_.
Strong emphasis, aka bold, with **asterisks** or __underscores__.
Combined emphasis with **asterisks and _underscores_**.
Strikethrough uses two tildes. ~~Scratch this.~~
EOF;
        $expected = '92df3efd6e94f77f072b9e44c4c81429';
        $this->assertEquals($expected, md5(Markdown::toHtml($text)));
        $this->assertEquals($expected, md5(Markdown::toHtml($text, ['escape' => false])));
    }

    public function testFromHtml()
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
        $expected = '4399129d48cd18aae041febbc2965de0';
        $this->assertEquals($expected, md5(Markdown::fromHtml($html)));
    }

    public function testToTextUnorderedList()
    {
        $html = '<span>%</span><ul><li>Item #1</li><li>Item #2</li></ul><span>%</span>';
        $expected = "\n* Item #1\n* Item #2\n";
        $this->assertContains($expected, Markdown::fromHtml($html));
    }

    public function testToTextNumberedList()
    {
        $html = '<span>%</span><ol><li>Item #1</li><li>Item #2</li></ol><span>%</span>';
        $expected = "\n1. Item #1\n2. Item #2\n";
        $this->assertContains($expected, Markdown::fromHtml($html));
    }

    public function testToTextSubList()
    {
        $html = '<h2>A Nested List</h2><p>List can be nested (lists inside lists):</p><ul> <li>Coffee</li><li>Tea<ul> <li>Black tea</li><li>Green tea</li></ul></li> <li>Milk</li></ul>';

        $expected = "\n* Coffee\n* Tea\n   * Black tea\n   * Green tea\n* Milk";
        $this->assertContains($expected, Markdown::fromHtml($html));
    }

    public function testToTextSubListOrdered()
    {
        $html = '<h2>A Nested List</h2><p>List can be nested (lists inside lists):</p><ul> <li>Coffee</li><li>Tea<ol> <li>Black tea</li><li>Green tea</li></ol></li> <li>Milk</li></ul>';

        $expected = "\n* Coffee\n* Tea\n   1. Black tea\n   2. Green tea\n* Milk";
        $this->assertContains($expected, Markdown::fromHtml($html));
    }

    public function testUrlSecurity()
    {
        $text = '[xss](javascript:alert%281%29)'; // Actual attack vector
        $this->assertContains('<a href="">xss</a>', Markdown::toHtml($text));
        $text = '![xss](javascript:alert%281%29)'; // just testing url is being removed
        $this->assertContains('<img src="" alt="xss">', Markdown::toHtml($text));
    }
}
