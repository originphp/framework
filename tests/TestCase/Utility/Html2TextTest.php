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

use Origin\Utility\Html2Text;

class Html2TextTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('This class is being deprecated');
    }
    public function testConvertHeaders()
    {
        $html = '<h1>Heading 1</h1><h2>Heading 2</h2><h3>Heading 3</h3>';
        $result = Html2Text::convert($html);
        $expected = "# Heading 1\n\n## Heading 2\n\n### Heading 3";

        $this->assertSame($expected, $result);
    }

    public function testConvertParagraph()
    {
        $html = '<p>Dear Sir</p><p>This is a test</p><p>James</p>';
        $result = Html2Text::convert($html);
        $expected = "\nDear Sir\n\nThis is a test\n\nJames";

        $this->assertSame($expected, $result);
    }

    public function testConvertList()
    {
        $html = '<ul><li>Buy milk</li><li>Call Tony</li></ul>';
        $result = Html2Text::convert($html);
        $expected = "- Buy milk\n- Call Tony";
        $this->assertSame($expected, $result);
    }

    public function testConvertCode()
    {
        $html = '<code>$ docker-compose up</code>';
        $result = Html2Text::convert($html);
        $expected = '`$ docker-compose up`';
        $this->assertSame($expected, $result);
    }

    public function testConvertDefinitionList()
    {
        $html = '<dl> <dt>OriginPHP</dt> <dd>A framework</dd> <dt>PHP</dt> <dd>The language used to build OriginPHP</dd> </dl>';
        $result = Html2Text::convert($html);
        $expected = "OriginPHP\n:  A framework\nPHP\n:  The language used to build OriginPHP";
        $this->assertSame($expected, $result);
    }

    public function testConvertLineBreaks()
    {
        $html = '<p>Dear Sir</p><p>Here is your pin:<br>007</p><p>James</p>';
        $result = Html2Text::convert($html);
        $expected = "\nDear Sir\n\nHere is your pin:\n007\n\nJames";
        $this->assertSame($expected, $result);

        $html = '<p>Dear Sir</p><p>Here is your pin:<br />007</p><p>James</p>';
        $result = Html2Text::convert($html);
        $expected = "\nDear Sir\n\nHere is your pin:\n007\n\nJames";
        $this->assertSame($expected, $result);
    }
    public function testConvertConvertTableRows()
    {
        $html = '<table> <tr> <th>Company</th> <th>Contact</th> <th>Country</th> </tr> <tr> <td>Alfreds Futterkiste</td> <td>Maria Anders</td> <td>Germany</td> </tr> <tr> <td>Centro comercial Moctezuma</td> <td>Francisco Chang</td> <td>Mexico</td> </tr> <tr> <td>Ernst Handel</td> <td>Roland Mendel</td> <td>Austria</td> </tr> <tr> <td>Island Trading</td> <td>Helen Bennett</td> <td>UK</td> </tr> <tr> <td>Laughing Bacchus Winecellars</td> <td>Yoshi Tannamuri</td> <td>Canada</td> </tr> <tr> <td>Magazzini Alimentari Riuniti</td> <td>Giovanni Rovelli</td> <td>Italy</td> </tr> </table>';

        $result = Html2Text::convert($html);
        $expected = "Company Contact Country \nAlfreds Futterkiste Maria Anders Germany \nCentro comercial Moctezuma Francisco Chang Mexico \nErnst Handel Roland Mendel Austria \nIsland Trading Helen Bennett UK \nLaughing Bacchus Winecellars Yoshi Tannamuri Canada \nMagazzini Alimentari Riuniti Giovanni Rovelli Italy";
        $this->assertSame($expected, $result);
    }
    public function testConvertBold()
    {
        $html = '<p>Dear Sir</p><p>Your password is <strong>1234</strong></p><p>James</p>';
        $result = Html2Text::convert($html);
        $expected = "\nDear Sir\n\nYour password is *1234*\n\nJames";
        $this->assertSame($expected, $result);
    }
    public function testConvertItalic()
    {
        $html = '<p>Dear Sir</p><p>This is an example of <em>Italic</em>.</p><p>James</p>';
        $result = Html2Text::convert($html);
        $expected = "\nDear Sir\n\nThis is an example of _Italic_.\n\nJames";
        $this->assertSame($expected, $result);

        $html = '<p>Dear Sir</p><p>This is an example of <i>Italic</i>.</p><p>James</p>';
        $result = Html2Text::convert($html);
        $expected = "\nDear Sir\n\nThis is an example of _Italic_.\n\nJames";
        $this->assertSame($expected, $result);
    }
    public function testConvertImage()
    {
        $html = '<p>Dear Sir</p><p>Today is going to be great.<img src="http://www.fantastic.com/images/great.png"></p><p>James</p>';
        $result = Html2Text::convert($html);
        $expected = "\nDear Sir\n\nToday is going to be great.[image: http://www.fantastic.com/images/great.png]\n\nJames";
        $this->assertSame($expected, $result);

        $html = '<p>Dear Sir</p><p>Today is going to be great too. <img src="http://www.fantastic.com/images/great.png" alt="Good to Great"></p><p>James</p>';
        $result = Html2Text::convert($html);
        $expected = "\nDear Sir\n\nToday is going to be great too. [image: Good to Great http://www.fantastic.com/images/great.png]\n\nJames";

        $this->assertSame($expected, $result);
    }
    public function testConvertLink()
    {
        $html = '<h1>Search Engines</h1><p>Using search engines is easy, try <a  href="http://www.google.com">Google</a>.</p><p>Author: Jim</p>';
        $result = Html2Text::convert($html);
        $expected = "# Search Engines\n\n\nUsing search engines is easy, try [Google](http://www.google.com).\n\nAuthor: Jim";

        $this->assertSame($expected, $result);

        $html = '<h1>Search Engines</h1><p>Using search engines is easy, try <a title="Google" href="http://www.google.com">Google</a>.</p><p>Author: Jim</p>';
        $result = Html2Text::convert($html);
        $expected = "# Search Engines\n\n\nUsing search engines is easy, try [Google](http://www.google.com).\n\nAuthor: Jim";
        $this->assertSame($expected, $result);
        #debug(replace("\n",'\n',$result));
        $html = '<a style="color: #0052cc; text-decoration: none;" href="tel:+44 207 400 433">+44 207 400 433</a></p>';
        $result = Html2Text::convert($html);

        $html = '<a style="color: #0052cc; text-decoration: none;" href="skype:skypeid">Contact me on skype</a></p>';
        $result = Html2Text::convert($html);
        $this->assertContains('skypeid', $result);

        $html = '<a style="color: #0052cc; text-decoration: none;" href="mailto:james@oracle.com">Email Me</a></p>';
        $result = Html2Text::convert($html);
        $this->assertContains('james@oracle.com', $result);

        # Test Other Link
        $html = '<p>Hi {FIRST_NAME}</p><p>Does&nbsp;{POSITION} accurately describe your position?</p><p>--<br />Jen Setler</p><p><a href="{UNSUBSCRIBE}">opt-out</a></p>';
        $result = Html2Text::convert($html);
        $this->assertContains('[opt-out]({UNSUBSCRIBE})', $result);
    }
}
